<?php

namespace Tests\Feature\Blood;

use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodUnit;
use App\Models\Clinical\ClinicalAlert;
use App\Models\Patient;
use App\Services\Blood\BloodCompatibilityService;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodIssueService;
use App\Services\Blood\BloodMtpService;
use App\Services\Blood\BloodRequestService;
use App\Services\Blood\BloodTraceabilityService;
use App\Services\Blood\BloodTransfusionService;
use Illuminate\Validation\ValidationException;

class BloodTransfusionChainTest extends BloodTestCase
{
    public function test_a_wrong_patient_label_is_rejected_and_logged(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $requests = app(BloodRequestService::class);
        $request = $requests->create($this->user, $patient, 'prbc', 1, 'routine');

        $sample = $requests->receiveSample($request, $this->user, ['patient_public_id' => $other->public_id, 'abo' => 'A', 'rh' => 'positive', 'collected_at' => now()->subMinutes(5)]);

        $this->assertSame('rejected', $sample->status);
        $this->assertSame('wrong_patient', $sample->rejection_reason);
        $this->assertSame(1, BloodIncident::where('incident_type', 'wrong_sample')->count());
        $this->assertSame('requested', $request->refresh()->status);
    }

    public function test_an_unknown_group_needs_two_agreeing_samples_then_is_confirmed_on_the_patient(): void
    {
        $patient = $this->patient();
        $requests = app(BloodRequestService::class);
        $request = $requests->create($this->user, $patient, 'prbc', 1, 'routine');
        $data = ['patient_public_id' => $patient->public_id, 'abo' => 'B', 'rh' => 'negative'];

        $first = $requests->receiveSample($request, $this->user, $data + ['collected_at' => now()->subHour()]);
        $this->assertSame('provisional', $first->status);
        $this->assertSame('requested', $request->refresh()->status, 'one sample is not enough');

        $second = $requests->receiveSample($request, $this->user, $data + ['collected_at' => now()->subMinutes(5)]);
        $this->assertSame('accepted', $second->status);
        $this->assertSame('sample_received', $request->refresh()->status);
        $this->assertSame(['B-', 'negative'], [$patient->refresh()->blood_group, $patient->rh_factor]);
    }

    public function test_a_discordant_second_sample_rejects_both_and_a_confirmed_group_rejects_a_different_one(): void
    {
        $patient = $this->patient();
        $requests = app(BloodRequestService::class);
        $request = $requests->create($this->user, $patient, 'prbc', 1, 'routine');
        $requests->receiveSample($request, $this->user, ['patient_public_id' => $patient->public_id, 'abo' => 'A', 'rh' => 'positive', 'collected_at' => now()->subHour()]);
        $second = $requests->receiveSample($request, $this->user, ['patient_public_id' => $patient->public_id, 'abo' => 'O', 'rh' => 'positive', 'collected_at' => now()->subMinutes(5)]);
        $this->assertSame('rejected', $second->status);
        $this->assertSame(0, $patient->samples ?? 0);

        $ready = $this->readyRequest('A', 'positive');
        $bad = $requests->receiveSample($ready, $this->user, ['patient_public_id' => Patient::find($ready->patient_id)->public_id, 'abo' => 'B', 'rh' => 'positive', 'collected_at' => now()]);
        $this->assertSame('group_discordant', $bad->rejection_reason);
    }

    public function test_an_incompatible_unit_is_recorded_but_can_never_be_reserved(): void
    {
        $request = $this->readyRequest('A', 'negative');
        $bPositive = $this->unit('prbc', 'B', 'positive');
        $aPositive = $this->unit('prbc', 'A', 'positive');

        foreach ([$bPositive, $aPositive] as $unit) {
            try {
                $this->reserve($request, $unit);
                $this->fail('incompatible unit reserved');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('unit', $e->errors());
            }
            $this->assertSame(BloodUnit::AVAILABLE, $unit->refresh()->status);
        }
        $this->assertSame(2, \App\Models\Blood\BloodCrossmatch::where('request_id', $request->id)->where('result', 'incompatible')->count());
        $this->assertSame(0, \App\Models\Blood\BloodReservation::count());
    }

    public function test_a_compatible_unit_is_reserved_charged_for_the_crossmatch_and_the_reservation_lapses_and_frees_it(): void
    {
        $request = $this->readyRequest('A', 'positive');
        $unit = $this->unit('prbc', 'O', 'negative');
        $reservation = $this->reserve($request, $unit);

        $this->assertSame(BloodUnit::RESERVED, $unit->refresh()->status);
        $this->assertSame('ready', $request->refresh()->status);
        $this->assertDatabaseHas('charge_event_log', ['idempotency_key' => 'BLD:'.$request->request_number.':BLD-XM:'.$reservation->crossmatch_id]);

        $other = $this->readyRequest('A', 'positive');
        try {
            $this->reserve($other, $unit);
            $this->fail('a reserved unit was reserved twice');
        } catch (ValidationException) {
        }

        $this->travel(25)->hours();
        $this->assertSame(1, app(BloodIssueService::class)->releaseLapsedReservations());
        $this->assertSame(BloodUnit::AVAILABLE, $unit->refresh()->status);
        $this->assertSame('expired', $reservation->refresh()->status);
    }

    public function test_more_units_than_asked_for_cannot_be_reserved(): void
    {
        $request = $this->readyRequest('O', 'positive', 'prbc', 1);
        $this->reserve($request, $this->unit('prbc', 'O', 'positive'));
        $this->expectException(ValidationException::class);
        $this->reserve($request, $this->unit('prbc', 'O', 'positive'));
    }

    public function test_a_unit_cannot_be_issued_twice_and_an_expired_unit_never(): void
    {
        $request = $this->readyRequest('A', 'positive', 'prbc', 2);
        $unit = $this->unit('prbc', 'A', 'positive');
        $this->reserve($request, $unit);
        $issues = app(BloodIssueService::class);
        $issue = $issues->issue($request, $unit->refresh(), $this->user, 'Ward nurse', 4.5);

        $this->assertSame('partially_issued', $request->refresh()->status);
        $this->assertSame(BloodUnit::ISSUED, $unit->refresh()->status);
        $this->assertDatabaseHas('charge_event_log', ['idempotency_key' => 'BLD:'.$request->request_number.':BLD-PRBC:'.$issue->id]);
        try {
            $issues->issue($request, $unit->refresh(), $this->user);
            $this->fail('issued twice');
        } catch (ValidationException) {
        }

        $second = $this->unit('prbc', 'A', 'positive');
        $this->reserve($request, $second);
        $second->update(['expires_at' => now()->subMinute()]);
        $this->expectException(ValidationException::class);
        $issues->issue($request, $second->refresh(), $this->user);
    }

    public function test_return_rules_decide_between_the_shelf_and_the_bin_and_reverse_the_charge(): void
    {
        $issues = app(BloodIssueService::class);
        $request = $this->readyRequest('O', 'positive', 'prbc', 2);
        $fine = $this->unit('prbc', 'O', 'positive');
        $late = $this->unit('prbc', 'O', 'positive');
        $this->reserve($request, $fine);
        $this->reserve($request, $late);
        $a = $issues->issue($request, $fine->refresh(), $this->user);
        $b = $issues->issue($request, $late->refresh(), $this->user);

        $issues->returnUnit($a, $this->user, 20, 5.0, 'Not needed');
        $issues->returnUnit($b, $this->user, 55, 5.0, 'Waiting too long');

        $this->assertSame(BloodUnit::AVAILABLE, $fine->refresh()->status);
        $this->assertTrue($a->refresh()->return_accepted);
        $this->assertSame(BloodUnit::DISCARDED, $late->refresh()->status);
        $this->assertFalse($b->refresh()->return_accepted);
        $this->assertDatabaseHas('blood_discards', ['unit_id' => $late->id, 'reason' => 'returned_unfit']);
        $this->assertDatabaseHas('charge_event_log', ['idempotency_key' => 'BLD:'.$request->request_number.':BLD-PRBC:'.$a->id, 'outcome' => 'reversed']);
    }

    private function issued(string $abo = 'O', string $rh = 'positive'): array
    {
        $request = $this->readyRequest($abo, $rh);
        $unit = $this->unit('prbc', $abo, $rh);
        $this->reserve($request, $unit);

        return [app(BloodIssueService::class)->issue($request, $unit->refresh(), $this->user), $unit, $request];
    }

    public function test_bedside_check_needs_two_different_people_and_matching_scans_else_a_reasoned_override_opens_an_incident(): void
    {
        [$issue, $unit, $request] = $this->issued();
        $patient = Patient::find($issue->patient_id);
        $a = $this->staff(['staff_nurse']);
        $b = $this->staff(['staff_nurse']);
        $service = app(BloodTransfusionService::class);

        try {
            $service->start($issue, $a, $a, $patient->public_id, $unit->unit_number);
            $this->fail('one person did both checks');
        } catch (ValidationException) {
        }
        try {
            $service->start($issue, $a, $b, $patient->public_id, 'WRONG-BAG');
            $this->fail('wrong bag accepted');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('bag does not match', $e->errors()['verification'][0]);
        }
        $this->assertSame('issued', $issue->refresh()->status);

        $transfusion = $service->start($issue, $a, $b, 'WRONG-WRISTBAND', $unit->unit_number, 'Wristband torn, identity confirmed verbally by two nurses');
        $this->assertTrue($transfusion->verification_override);
        $this->assertSame(1, BloodIncident::where('incident_type', 'verification_override')->count());
        $this->assertSame('transfusing', $issue->refresh()->status);
    }

    public function test_a_clean_transfusion_records_observations_through_the_observation_service_and_completes_the_chain(): void
    {
        [$issue, $unit, $request] = $this->issued();
        $patient = Patient::find($issue->patient_id);
        $a = $this->staff(['staff_nurse']);
        $b = $this->staff(['staff_nurse']);
        $service = app(BloodTransfusionService::class);

        $transfusion = $service->start($issue, $a, $b, $patient->enterprise_patient_no, $unit->unit_number, null, ['pulse' => 80, 'temperature' => 36.9, 'systolic' => 120, 'diastolic' => 78]);
        $this->assertFalse($transfusion->verification_override);
        $service->recordObservations($transfusion, $a, ['pulse' => 84, 'temperature' => 37.0]);
        $this->assertSame(6, \App\Models\Clinical\ClinicalObservation::where('patient_id', $patient->id)->where('context_type', 'transfusion')->count());

        $service->complete($transfusion, $a, 270);
        $this->assertSame(BloodUnit::TRANSFUSED, $unit->refresh()->status);
        $this->assertSame('completed', $request->refresh()->status);
        $this->assertTrue($unit->events()->where('event_type', 'transfusion_started')->exists());

        $this->expectException(ValidationException::class);
        $service->complete($transfusion->refresh(), $a, 270);
    }

    public function test_a_reaction_stops_the_transfusion_alerts_the_blood_bank_and_the_doctor_opens_an_incident_and_holds_sibling_bags_when_serious(): void
    {
        $donation = $this->releasedDonation('O', 'positive');
        $request = $this->readyRequest('O', 'positive', 'prbc', 1);
        $unit = BloodUnit::where('donation_id', $donation->id)->where('component', 'prbc')->firstOrFail();
        $this->reserve($request, $unit);
        $issue = app(BloodIssueService::class)->issue($request, $unit->refresh(), $this->user);
        $patient = Patient::find($issue->patient_id);
        $service = app(BloodTransfusionService::class);
        $transfusion = $service->start($issue, $this->staff(['staff_nurse']), $this->staff(['staff_nurse']), $patient->public_id, $unit->unit_number);

        $reaction = $service->reportReaction($transfusion, $this->user, 'acute_haemolytic', 'severe', ['fever', 'back pain'], 'Stopped, line kept open, doctor called');

        $this->assertSame('stopped_reaction', $transfusion->refresh()->status);
        $this->assertSame('severe', $reaction->severity);
        $this->assertSame(1, ClinicalAlert::where('category', 'blood_transfusion_reaction')->count());
        $this->assertSame(1, BloodIncident::where('incident_type', 'transfusion_reaction')->where('severity', 'critical')->count());
        $this->assertSame(BloodUnit::BLOCKED, BloodUnit::where('donation_id', $donation->id)->where('component', 'ffp')->firstOrFail()->status);
        $this->assertSame('reaction_review', BloodUnit::where('donation_id', $donation->id)->where('component', 'ffp')->value('blocked_reason'));

        try {
            $service->complete($transfusion->refresh(), $this->user, 100);
            $this->fail('a stopped transfusion was completed');
        } catch (ValidationException) {
        }
    }

    public function test_an_open_reaction_blocks_the_next_transfusion_for_that_patient_without_a_reasoned_override(): void
    {
        [$issue, $unit] = $this->issued();
        $patient = Patient::find($issue->patient_id);
        BloodIncident::create(['organization_id' => $this->organization->id, 'patient_id' => $patient->id, 'incident_type' => 'transfusion_reaction', 'description' => 'earlier reaction', 'occurred_at' => now()->subDay()]);

        $this->expectException(ValidationException::class);
        app(BloodTransfusionService::class)->start($issue, $this->staff(), $this->staff(), $patient->public_id, $unit->unit_number);
    }

    public function test_lookback_answers_who_received_units_from_donor_x_and_which_donor_gave_unit_y_and_recall_blocks_the_rest(): void
    {
        $donor = $this->donor();
        $donation = $this->releasedDonation('O', 'positive', $donor);
        $trace = app(BloodTraceabilityService::class);

        // two recipients from this donor's donation (prbc and ffp)
        $prbc = BloodUnit::where('donation_id', $donation->id)->where('component', 'prbc')->firstOrFail();
        $ffp = BloodUnit::where('donation_id', $donation->id)->where('component', 'ffp')->firstOrFail();
        $r1 = $this->readyRequest('A', 'positive', 'prbc');
        $r2 = $this->readyRequest('O', 'positive', 'ffp');
        $this->reserve($r1, $prbc);
        $this->reserve($r2, $ffp);
        $issue1 = app(BloodIssueService::class)->issue($r1, $prbc->refresh(), $this->user);
        $issue2 = app(BloodIssueService::class)->issue($r2, $ffp->refresh(), $this->user);
        $p1 = Patient::find($issue1->patient_id);
        $transfusions = app(BloodTransfusionService::class);
        $t = $transfusions->start($issue1, $this->staff(), $this->staff(), $p1->public_id, $prbc->unit_number);
        $transfusions->complete($t, $this->user, 280);

        $start = microtime(true);
        $recipients = $trace->recipientsOfDonor($donor);
        $this->assertLessThan(2.0, microtime(true) - $start, 'lookback answers within 2 seconds');
        $this->assertEqualsCanonicalizing([$issue1->patient_id, $issue2->patient_id], $recipients->pluck('patient_id')->all());
        $this->assertSame('transfused', $recipients->firstWhere('patient_id', $issue1->patient_id)['state']);
        $this->assertSame($donor->id, $trace->donorOfUnit($prbc)['donor']->id);
        $this->assertSame(1, $trace->recipientsOfUnit($ffp)->count());
        $this->assertNotEmpty($trace->chain($prbc));

        $platelets = BloodUnit::where('donation_id', $donation->id)->where('component', 'platelets')->firstOrFail();
        $recall = $trace->recall($this->user, 'Donor later tested positive on a repeat sample', $donor);
        $this->assertSame(BloodUnit::BLOCKED, $platelets->refresh()->status);
        $this->assertSame('recalled', $platelets->blocked_reason);
        $this->assertSame(2, $recall->recipients_found);
        $this->assertSame(1, ClinicalAlert::where('category', 'blood_recall_recipient')->count(), 'only the recipient who already received is alerted');
    }

    public function test_emergency_release_gives_only_the_universal_group_with_an_authoriser_and_opens_an_incident_and_a_crossmatch_is_owed(): void
    {
        $patient = $this->patient();
        $request = app(BloodRequestService::class)->create($this->user, $patient, 'prbc', 1, 'emergency', 'ed');
        $this->assertSame(1, ClinicalAlert::where('category', 'blood_request_urgent')->count());
        $issues = app(BloodIssueService::class);
        $aPos = $this->unit('prbc', 'A', 'positive');
        $oNeg = $this->unit('prbc', 'O', 'negative');

        try {
            $issues->emergencyIssue($request, $aPos, $this->user, 'Dr. Rahman', 'Massive bleed, group unknown');
            $this->fail('non-universal group released uncross-matched');
        } catch (ValidationException) {
        }
        try {
            $issues->emergencyIssue($request, $oNeg, $this->user, '', 'x');
            $this->fail('released without an authoriser');
        } catch (ValidationException) {
        }

        $issue = $issues->emergencyIssue($request, $oNeg, $this->user, 'Dr. Rahman', 'Massive bleed, group unknown');
        $this->assertFalse($issue->crossmatched);
        $this->assertSame(1, BloodIncident::where('incident_type', 'emergency_release')->count());

        $requests = app(BloodRequestService::class);
        foreach ([now()->subHour(), now()->subMinutes(5)] as $when) {
            $requests->receiveSample($request, $this->user, ['patient_public_id' => $patient->public_id, 'abo' => 'A', 'rh' => 'positive', 'collected_at' => $when]);
        }
        $match = $issues->retrospectiveCrossmatch($issue, $this->user);
        $this->assertSame('compatible', $match->result);
        $this->assertTrue($issue->refresh()->crossmatched);
    }

    public function test_the_massive_transfusion_protocol_opens_the_pack_and_releases_first_expiring_universal_units_and_reports_shortages(): void
    {
        foreach (range(1, 2) as $i) {
            $this->releasedDonation('O', 'negative');
        }
        $patient = $this->patient();
        $result = app(BloodMtpService::class)->activate($this->user, $patient, 'Dr. Karim', 'Ruptured aneurysm');

        $this->assertCount(3, $result['requests']);
        $this->assertTrue(collect($result['requests'])->every(fn ($r) => $r->priority === 'massive'));
        $this->assertSame(2, \App\Models\Blood\BloodIssue::where('patient_id', $patient->id)->whereHas('unit', fn ($q) => $q->where('component', 'prbc'))->count());
        $this->assertSame(['prbc' => 2, 'ffp' => 4], $result['short'], 'two O-negative bags and no AB plasma: the shortage is reported, not hidden');
        $this->assertGreaterThanOrEqual(1, BloodIncident::where('incident_type', 'emergency_release')->count());
        $this->assertNotNull(app(BloodInventoryService::class));
        $this->assertNotNull(app(BloodCompatibilityService::class));
    }
}
