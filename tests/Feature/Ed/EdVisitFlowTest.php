<?php

namespace Tests\Feature\Ed;

use App\Models\Clinical\ClinicalAlert;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Ed\EdVisit;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Ed\EdBayService;
use App\Services\Ed\EdTimerService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** P12 S02–S05: arrival, unknown patient, triage, bays, treatment start, timers. */
class EdVisitFlowTest extends EdTestCase
{
    private function arrive(array $data = []): EdVisit
    {
        return app(EdVisitService::class)->arrive($this->user, $this->branch, $data + ['arrival_mode' => 'ambulance', 'chief_complaint' => 'Chest pain']);
    }

    public function test_an_unknown_patient_is_registered_with_a_temporary_identity_an_encounter_and_a_visit_number(): void
    {
        $visit = $this->arrive(['sex' => 'M', 'estimated_age' => 45, 'description' => 'Brought by police, blue shirt']);

        $this->assertTrue($visit->is_unknown_patient);
        $this->assertSame('awaiting_triage', $visit->status);
        $this->assertMatchesRegularExpression('/^EDV-\d{4}-\d{8}$/', $visit->visit_number);
        $this->assertTrue($visit->patient->is_unknown);
        $this->assertTrue($visit->patient->is_temporary);
        $this->assertMatchesRegularExpression('/^UNKNOWN-\d{4}-\d{6}$/', $visit->patient->last_name);
        $this->assertNotNull($visit->patient->enterprise_patient_no, 'a wristband number exists immediately');
        $this->assertSame('Emergency', $visit->encounter->encounter_type);
        $this->assertTrue($visit->triage_due_at->equalTo($visit->arrival_at->copy()->addMinutes(10)));
        $this->assertSame(['awaiting_triage'], $visit->statusHistory->pluck('to_status')->all());
    }

    public function test_a_known_patient_cannot_have_two_open_visits(): void
    {
        $patient = $this->patient();
        $this->arrive(['patient_id' => $patient->id]);

        $this->expectException(ValidationException::class);
        $this->arrive(['patient_id' => $patient->id]);
    }

    public function test_a_patient_of_another_organization_cannot_be_registered(): void
    {
        $foreign = Patient::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->expectException(ModelNotFoundException::class);
        $this->arrive(['patient_id' => $foreign->id]);
    }

    public function test_triage_writes_vitals_through_the_observation_service_and_starts_the_doctor_clock(): void
    {
        $visit = $this->arrive();

        $record = app(EdTriageService::class)->triage($visit, $this->user, [
            'level' => 2, 'complaint' => 'Crushing chest pain',
            'vitals' => ['temperature' => 37.2, 'pulse' => 118, 'respiratory_rate' => 24, 'oxygen_saturation' => 93, 'systolic' => 90, 'diastolic' => 60],
        ]);

        $visit->refresh();
        $this->assertSame('waiting', $visit->status);
        $this->assertSame(2, $visit->triage_level);
        $this->assertFalse($record->is_retriage);
        $this->assertTrue($visit->doctor_due_at->equalTo($visit->arrival_at->copy()->addMinutes(10)));
        $this->assertTrue($visit->retriage_due_at->greaterThan(now()));

        $observations = ClinicalObservation::where('context_type', 'ed')->where('context_id', $visit->id)->get();
        $this->assertEqualsCanonicalizing(['8310-5', '8867-4', '9279-1', '2708-6', '8480-6', '8462-4'], $observations->pluck('code')->all());
        $this->assertNotNull($record->observation_set_id, 'blood pressure is one panel');
        $this->assertSame(0, Schema::hasTable('ed_monitoring_records') ? 1 : 0, 'no copy of vital values in an ED table');
    }

    public function test_bad_triage_input_is_refused_without_writing_anything(): void
    {
        $visit = $this->arrive();
        $service = app(EdTriageService::class);

        foreach ([['level' => 9], ['level' => 3, 'vitals' => ['systolic' => 120]], ['level' => 3, 'vitals' => ['pulse' => 900]]] as $bad) {
            try {
                $service->triage($visit, $this->user, $bad);
                $this->fail('expected a validation error');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, $visit->triageRecords()->count());
        $this->assertSame(0, ClinicalObservation::count());
        $this->assertSame('awaiting_triage', $visit->fresh()->status);
    }

    public function test_level_one_raises_a_critical_alert_to_the_duty_staff_once(): void
    {
        $doctor = $this->staff(['doctor']);
        $nurse = $this->staff(['charge_nurse']);
        $visit = $this->arrive();

        app(EdTriageService::class)->triage($visit, $this->user, ['level' => 1]);
        app(EdTriageService::class)->triage($visit->fresh(), $this->user, ['level' => 1]);

        $alert = ClinicalAlert::where('category', 'ed_resuscitation')->firstOrFail();
        $this->assertSame('critical', $alert->severity);
        $this->assertEqualsCanonicalizing([$doctor->id, $nurse->id], $alert->recipients()->pluck('user_id')->all());
        $this->assertSame(1, ClinicalAlert::where('category', 'ed_resuscitation')->count());
    }

    public function test_retriage_keeps_the_original_arrival_clock_and_is_flagged(): void
    {
        $visit = $this->arrive();
        $service = app(EdTriageService::class);
        $service->triage($visit, $this->user, ['level' => 4]);
        $arrival = $visit->fresh()->arrival_at;

        $this->travel(20)->minutes();
        $second = $service->triage($visit->fresh(), $this->user, ['level' => 2]);

        $visit->refresh();
        $this->assertTrue($second->is_retriage);
        $this->assertSame(2, $visit->triage_level);
        $this->assertTrue($visit->doctor_due_at->equalTo($arrival->copy()->addMinutes(10)), 'target is measured from arrival, not from re-triage');
        $this->assertSame(2, $visit->triageRecords()->count());
    }

    public function test_two_staff_cannot_take_the_same_bay_and_a_move_frees_the_old_bay(): void
    {
        $bays = app(EdBayService::class);
        $a = $this->arrive();
        $b = $this->arrive();
        $bay1 = $this->bay('A1');
        $bay2 = $this->bay('A2');

        $bays->assign($a, $bay1, $this->user);

        try {
            $bays->assign($b, $bay1, $this->user);
            $this->fail('the bay is taken');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('not available', $e->getMessage());
        }
        $this->assertNull($b->fresh()->bay_id);

        $bays->assign($a->fresh(), $bay2, $this->user);       // move
        $this->assertSame('cleaning', $bay1->fresh()->status);
        $this->assertNull($bay1->fresh()->current_visit_id);
        $this->assertSame($a->id, $bay2->fresh()->current_visit_id);

        $bays->setStatus($bay1->fresh(), 'available');
        $bays->assign($b->fresh(), $bay1->fresh(), $this->user);
        $this->assertSame(3, DB::table('ed_bay_assignments')->count());
        $this->assertSame(1, DB::table('ed_bay_assignments')->where('visit_id', $a->id)->whereNull('released_at')->count());
    }

    public function test_the_database_itself_refuses_one_visit_in_two_bays(): void
    {
        $visit = $this->arrive();
        $this->bay('A1')->update(['current_visit_id' => $visit->id]);
        $second = $this->bay('A2');

        $this->expectException(QueryException::class);
        $second->update(['current_visit_id' => $visit->id]);
    }

    public function test_occupied_bays_cannot_be_blocked_or_cleaned_by_hand(): void
    {
        $visit = $this->arrive();
        $bay = $this->bay('A1');
        app(EdBayService::class)->assign($visit, $bay, $this->user);

        $this->expectException(ValidationException::class);
        app(EdBayService::class)->setStatus($bay->fresh(), 'blocked');
    }

    public function test_treatment_start_records_the_first_doctor_contact_once(): void
    {
        $visit = $this->arrive();
        app(EdTriageService::class)->triage($visit, $this->user, ['level' => 3]);
        $doctor = $this->staff(['doctor']);

        $started = app(EdVisitService::class)->startTreatment($visit->fresh(), $doctor);
        $first = $started->doctor_seen_at;
        $this->assertSame('in_treatment', $started->status);
        $this->assertSame($doctor->id, $started->doctor_id);
        $this->assertNull($started->retriage_due_at);

        $this->expectException(ValidationException::class);
        app(EdVisitService::class)->startTreatment($started, $doctor);
    }

    public function test_timers_alert_once_for_late_triage_late_doctor_and_due_retriage(): void
    {
        $this->staff(['doctor']);
        $this->arrive();
        $waiting = $this->arrive();
        app(EdTriageService::class)->triage($waiting, $this->user, ['level' => 3]);
        $timers = app(EdTimerService::class);

        $this->assertSame(0, $timers->check(), 'nothing is late yet');

        $this->travel(45)->minutes();
        $first = $timers->check();
        $categories = ClinicalAlert::pluck('category')->all();

        $this->assertGreaterThanOrEqual(3, $first);
        $this->assertContains('ed_triage_overdue', $categories);
        $this->assertContains('ed_door_to_doctor', $categories);
        $this->assertContains('ed_retriage_due', $categories);
        $this->assertSame(0, $timers->check(), 'running again does not raise the same alerts');
    }

    public function test_a_doctor_contact_stops_the_door_to_doctor_alert(): void
    {
        $visit = $this->arrive();
        app(EdTriageService::class)->triage($visit, $this->user, ['level' => 3]);
        app(EdVisitService::class)->startTreatment($visit->fresh(), $this->staff(['doctor']));

        $this->travel(3)->hours();

        $this->assertSame(0, app(EdTimerService::class)->check());
        $this->assertSame(0, ClinicalAlert::count());
    }
}
