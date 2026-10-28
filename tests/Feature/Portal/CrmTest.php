<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\Crm\CrmCampaign;
use App\Models\Crm\CrmCampaignRecipient;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmComplaintEvent;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmFormSubmission;
use App\Models\Crm\CrmInteraction;
use App\Models\Crm\CrmSegment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\PatientProblem;
use App\Models\Portal\PortalOutbox;
use App\Models\User;
use App\Services\Crm\CampaignService;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FeedbackService;
use App\Services\Crm\FormService;
use App\Services\Crm\InteractionService;
use App\Services\Crm\SegmentService;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\PortalException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmTest extends PortalTestCase
{
    private function segment(array $criteria = [], ?string $basis = null, ?User $by = null)
    {
        return app(SegmentService::class)->create($this->organization->id, $by ?? $this->actor(['crm.segment.manage']), 'Group '.uniqid(), $criteria, null, $basis);
    }

    private function activate(CrmSegment $s, ?User $by = null): CrmSegment
    {
        return app(SegmentService::class)->activate($s, $by ?? $this->actor(['crm.segment.approve']));
    }

    // ---- segments -------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_group_selects_by_age_sex_and_portal_use(): void
    {
        $this->patient(['date_of_birth' => now()->subYears(30)->toDateString(), 'sex' => 'F']);
        $this->patient(['date_of_birth' => now()->subYears(30)->toDateString(), 'sex' => 'M']);
        $this->patient(['date_of_birth' => now()->subYears(70)->toDateString(), 'sex' => 'F']);
        $svc = app(SegmentService::class);

        $this->assertSame(1, $svc->count($this->segment(['age_from' => 20, 'age_to' => 40, 'sex' => 'F'])));
        $this->assertSame(3, $svc->count($this->segment([])));
        $this->account($this->patient(['date_of_birth' => now()->subYears(25)->toDateString()]));
        $this->assertSame(1, $svc->count($this->segment(['has_portal' => true])));
    }

    public function test_a_group_never_includes_the_dead_the_merged_or_other_hospitals(): void
    {
        $this->patient();
        $this->patient(['deceased_at' => now()->subDay()]);
        $this->patient(['status' => 'inactive']);
        Patient::factory()->create();
        $this->assertSame(1, app(SegmentService::class)->count($this->segment([])));
    }

    public function test_only_known_conditions_are_accepted(): void
    {
        $this->expectException(ValidationException::class);
        $this->segment(['sql' => '1=1']);
    }

    public function test_lapsed_patients_are_found_by_last_visit(): void
    {
        $old = $this->patient();
        $recent = $this->patient();
        $never = $this->patient();
        foreach ([[$old, 200], [$recent, 10]] as [$p, $days]) {
            Encounter::create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $p->id, 'encounter_no' => 'E-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->actor([])->id,
                'encounter_date' => now()->subDays($days)->toDateString(), 'status' => 'completed', 'source' => 'walk_in', 'created_by' => $this->actor([])->id]);
        }
        $svc = app(SegmentService::class);
        $ids = $svc->patients($this->segment(['last_visit_before_days' => 90]))->pluck('patients.id')->all();

        $this->assertSame([$old->id], $ids);
        $this->assertNotContains($never->id, $ids);
    }

    public function test_a_diagnosis_based_group_needs_a_legal_basis_a_second_approver_and_only_includes_those_who_consented(): void
    {
        $author = $this->actor(['crm.segment.manage']);
        $a = $this->patient();
        $b = $this->patient();
        foreach ([$a, $b] as $p) {
            PatientProblem::query()->create(['patient_id' => $p->id, 'organization_id' => $this->organization->id, 'problem_code' => 'E11.9', 'problem_name' => 'Type 2 diabetes', 'coding_system' => 'ICD-10', 'status' => 'active']);
        }
        PatientConsent::query()->create(['organization_id' => $this->organization->id, 'patient_id' => $a->id, 'consent_type' => 'clinical_marketing', 'version' => 1, 'status' => 'active', 'granted_at' => now()]);

        try {
            $this->segment(['problem_code_prefix' => 'E11'], null, $author);
            $this->fail('created without a legal basis');
        } catch (ValidationException) {
        }
        $segment = $this->segment(['problem_code_prefix' => 'E11'], 'Diabetes education programme, consent form CF-12', $author);
        try {
            $this->activate($segment, $author);
            $this->fail('author approved their own clinical group');
        } catch (PortalException $e) {
            $this->assertSame(403, $e->status);
        }
        $active = $this->activate($segment);
        $this->assertSame(1, $active->last_count);
        $this->assertSame([$a->id], app(SegmentService::class)->patients($active)->pluck('patients.id')->all());
    }

    // ---- campaigns ------------------------------------------------------------------------------------------------------------------------------------

    private function campaign(array $o = [])
    {
        $segment = $o['segment'] ?? $this->activate($this->segment([]));

        return app(CampaignService::class)->create($this->organization->id, $o['by'] ?? $this->actor(['crm.campaign.manage']), array_merge(['name' => 'Camp', 'purpose' => 'marketing', 'channel' => 'sms', 'segment_id' => $segment->id, 'body' => 'Hello {name}, health camp on Friday.'], $o['data'] ?? []));
    }

    public function test_a_campaign_cannot_start_until_a_different_person_approves_it(): void
    {
        $author = $this->actor(['crm.campaign.manage']);
        $c = $this->campaign(['by' => $author]);
        $svc = app(CampaignService::class);

        $this->expectException(PortalException::class);
        $svc->run($c); // still a draft
    }

    public function test_the_author_cannot_approve_their_own_campaign(): void
    {
        $author = $this->actor(['crm.campaign.manage', 'crm.campaign.approve']);
        $svc = app(CampaignService::class);
        $c = $svc->submit($this->campaign(['by' => $author]));

        try {
            $svc->approve($c, $author);
            $this->fail('self approval');
        } catch (PortalException $e) {
            $this->assertSame(403, $e->status);
        }
        $this->assertSame('approved', $svc->approve($c, $this->actor(['crm.campaign.approve']))->status);
    }

    public function test_marketing_reaches_only_those_who_said_yes_and_every_skip_has_a_reason(): void
    {
        $yes = $this->patient(['phone' => '01711111111', 'first_name' => 'Amina']);
        $no = $this->patient(['phone' => '01722222222']);
        $stopped = $this->patient(['phone' => '01733333333']);
        $comms = app(CommunicationService::class);
        $comms->setPreference($yes, 'sms', 'marketing', true);
        $comms->setPreference($stopped, 'sms', 'marketing', true);
        $comms->optOutAll($stopped);
        $svc = app(CampaignService::class);
        $c = $svc->submit($this->campaign());
        $svc->approve($c, $this->actor(['crm.campaign.approve']));

        $done = $svc->run($c->fresh());

        $this->assertSame('completed', $done->status);
        $by = $done->stats;
        $this->assertSame(1, $by['queued'] ?? 0, 'the SMS goes to the outbox as queued because no SMS provider is installed');
        $this->assertSame(2, $by['suppressed']);
        $this->assertStringContainsString('Amina', PortalOutbox::query()->where('patient_id', $yes->id)->first()->body);
        $this->assertSame(['no opt-in for marketing', 'patient opted out'], CrmCampaignRecipient::query()->where('status', 'suppressed')->orderBy('id')->pluck('reason')->all());
        $this->assertNotNull($no->id);
    }

    public function test_running_a_campaign_twice_never_messages_anyone_twice(): void
    {
        $p = $this->patient(['phone' => '01711111111']);
        app(CommunicationService::class)->setPreference($p, 'sms', 'marketing', true);
        $svc = app(CampaignService::class);
        $c = $svc->submit($this->campaign());
        $svc->approve($c, $this->actor(['crm.campaign.approve']));
        $svc->run($c->fresh());
        CrmCampaign::query()->update(['status' => 'approved']);
        $svc->run($c->fresh());

        $this->assertSame(1, PortalOutbox::query()->where('template', 'campaign')->count());
    }

    public function test_a_campaign_in_quiet_hours_is_held_until_morning(): void
    {
        Carbon::setTestNow('2026-09-10 17:30:00'); // 23:30 in Dhaka
        $p = $this->patient(['phone' => '01711111111']);
        app(CommunicationService::class)->setPreference($p, 'sms', 'service', true);
        $svc = app(CampaignService::class);
        $c = $svc->submit($this->campaign(['data' => ['purpose' => 'service']]));
        $svc->approve($c, $this->actor(['crm.campaign.approve']));

        $this->assertSame(1, $svc->run($c->fresh())->stats['deferred']);
    }

    public function test_a_group_not_approved_cannot_be_used(): void
    {
        $this->expectException(PortalException::class);
        app(CampaignService::class)->submit($this->campaign(['segment' => $this->segment([])]));
    }

    // ---- feedback ----------------------------------------------------------------------------------------------------------------------------------------

    private function visit($patient, string $status = 'completed', int $daysAgo = 1): Appointment
    {
        return Appointment::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'appointment_date' => now()->subDays($daysAgo)->toDateString(), 'appointment_time' => '10:00', 'status' => $status]);
    }

    public function test_a_finished_visit_can_be_rated_once(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $visit = $this->visit($patient);

        $this->postJson("/api/v1/portal/appointments/{$visit->public_id}/feedback", ['rating' => 4, 'nps' => 9, 'comment' => 'Good doctor'], $headers)->assertCreated();
        $this->postJson("/api/v1/portal/appointments/{$visit->public_id}/feedback", ['rating' => 5], $headers)->assertStatus(409);
        $this->assertSame(1, CrmFeedback::query()->count());
    }

    public function test_a_poor_rating_opens_a_follow_up_for_the_relations_team(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $good = $this->visit($patient);
        $bad = $this->visit($patient);

        $this->postJson("/api/v1/portal/appointments/{$good->public_id}/feedback", ['rating' => 5], $headers)->assertCreated();
        $this->assertSame(0, CrmInteraction::query()->count());
        $this->postJson("/api/v1/portal/appointments/{$bad->public_id}/feedback", ['rating' => 1, 'comment' => 'Rude staff'], $headers)->assertCreated();

        $task = CrmInteraction::query()->first();
        $this->assertSame('open', $task->status);
        $this->assertSame($patient->id, $task->patient_id);
        $this->assertSame(now()->addDay()->toDateString(), $task->follow_up_on->toDateString());
        $this->assertNull($task->created_by);
    }

    public function test_a_visit_not_finished_or_too_old_or_someone_elses_cannot_be_rated(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $future = $this->visit($patient, 'scheduled', 0);
        $old = $this->visit($patient, 'completed', 40);
        $theirs = $this->visit($this->patient());

        $this->postJson("/api/v1/portal/appointments/{$future->public_id}/feedback", ['rating' => 4], $headers)->assertStatus(409);
        $this->postJson("/api/v1/portal/appointments/{$old->public_id}/feedback", ['rating' => 4], $headers)->assertStatus(409);
        $this->postJson("/api/v1/portal/appointments/{$theirs->public_id}/feedback", ['rating' => 4], $headers)->assertNotFound();
        $this->postJson("/api/v1/portal/appointments/{$future->public_id}/feedback", ['rating' => 9], $headers)->assertStatus(422);
    }

    public function test_the_summary_counts_the_average_the_net_promoter_score_and_low_ratings(): void
    {
        $svc = app(FeedbackService::class);
        foreach ([[5, 10], [4, 9], [2, 3], [1, 0]] as [$r, $n]) {
            $p = $this->patient();
            $svc->submit($p, null, $r, $n, null);
        }
        $this->assertSame(['count' => 4, 'average' => 3.0, 'nps' => 0, 'low' => 2], $svc->summary($this->organization->id));
    }

    // ---- complaints ----------------------------------------------------------------------------------------------------------------------------------------

    public function test_a_complaint_gets_a_number_a_deadline_and_a_trail(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $res = $this->postJson('/api/v1/portal/complaints', ['category' => 'waiting_time', 'description' => 'I waited three hours', 'severity' => 'high'], $headers)->assertCreated();

        $c = CrmComplaint::query()->first();
        $this->assertSame($res->json('data.number'), $c->number);
        $this->assertMatchesRegularExpression('/^CMP-2026-\d{5}$/', $c->number);
        $this->assertSame(now()->addHours(24)->toDateTimeString(), $c->due_at->toDateTimeString());
        $this->assertSame(['received'], $c->id ? CrmComplaintEvent::query()->where('complaint_id', $c->id)->pluck('kind')->all() : []);
    }

    public function test_a_complaint_moves_through_the_steps_in_order_and_the_complainant_rates_the_answer_once(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $number = $this->postJson('/api/v1/portal/complaints', ['category' => 'billing', 'description' => 'Charged twice'], $headers)->json('data.number');
        $c = CrmComplaint::query()->where('number', $number)->first();
        $svc = app(ComplaintService::class);
        $agent = $this->actor(['crm.complaint.manage']);
        $closer = $this->actor(['crm.complaint.close']);

        $svc->assign($c, $agent, $closer);
        $svc->investigate($c->fresh(), $agent, 'Checking the ledger');
        try {
            $svc->close($c->fresh(), $closer);
            $this->fail('closed before resolving');
        } catch (PortalException) {
        }
        try {
            $svc->resolve($c->fresh(), $agent, '  ');
            $this->fail('resolved without saying what was done');
        } catch (ValidationException) {
        }
        $svc->resolve($c->fresh(), $agent, 'Duplicate charge reversed');
        $this->getJson('/api/v1/portal/complaints', $headers)->assertJsonPath('data.0.answer', 'Duplicate charge reversed');
        $this->postJson("/api/v1/portal/complaints/$number/rating", ['score' => 4], $headers)->assertOk();
        $this->postJson("/api/v1/portal/complaints/$number/rating", ['score' => 5], $headers)->assertStatus(409);
        $svc->close($c->fresh(), $closer);

        $this->assertSame(['received', 'assigned', 'investigating', 'resolved', 'rated', 'closed'], CrmComplaintEvent::query()->where('complaint_id', $c->id)->orderBy('id')->pluck('kind')->all());
        $this->expectException(PortalException::class);
        $svc->investigate($c->fresh(), $agent, 'again');
    }

    public function test_a_complainant_does_not_see_the_answer_until_it_is_given_and_not_others_complaints(): void
    {
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $this->postJson('/api/v1/portal/complaints', ['category' => 'billing', 'description' => 'Charged twice'], $headers)->assertCreated();
        $this->getJson('/api/v1/portal/complaints', $headers)->assertJsonPath('data.0.answer', null);

        $other = $this->bearer($this->account($this->patient()));
        $this->getJson('/api/v1/portal/complaints', $other)->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/portal/complaints/'.CrmComplaint::query()->first()->number.'/rating', ['score' => 5], $other)->assertNotFound();
    }

    public function test_overdue_complaints_are_escalated_once(): void
    {
        app(ComplaintService::class)->lodge($this->organization->id, ['category' => 'billing', 'description' => 'x', 'severity' => 'high']);
        $this->travel(25)->hours();

        $this->assertSame(1, app(ComplaintService::class)->escalateOverdue());
        $this->assertSame(0, app(ComplaintService::class)->escalateOverdue());
        $this->assertSame('escalated', CrmComplaint::query()->first()->status);
    }

    // ---- interactions and forms -------------------------------------------------------------------------------------------------------------------------------

    public function test_an_interaction_with_a_follow_up_stays_open_and_belongs_to_the_logger(): void
    {
        $user = $this->actor(['crm.interaction.manage']);
        $row = app(InteractionService::class)->log($this->organization->id, $user, ['channel' => 'call', 'subject' => 'Asked about MRI price', 'contact_name' => 'Mr Hasan', 'contact_phone' => '01711111111', 'follow_up_on' => now()->addDays(2)->toDateString()]);

        $this->assertSame('open', $row->status);
        $this->assertSame($user->id, $row->assigned_to);
        $this->assertSame('done', app(InteractionService::class)->complete($row, 'Quoted the price')->status);
        $this->expectException(ValidationException::class);
        app(InteractionService::class)->log($this->organization->id, $user, ['channel' => 'carrier_pigeon', 'subject' => 'x']);
    }

    public function test_a_form_validates_answers_stores_them_encrypted_and_keeps_old_versions_readable(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $svc = app(FormService::class);
        $form = $svc->define($this->organization->id, 'pre-visit', 'Before your visit', [
            ['key' => 'smoker', 'label' => 'Do you smoke?', 'type' => 'yesno', 'required' => true],
            ['key' => 'weight', 'label' => 'Weight (kg)', 'type' => 'number'],
        ]);
        $headers = $this->bearer($account);

        $this->postJson('/api/v1/portal/forms/pre-visit', ['answers' => ['weight' => 'heavy']], $headers)->assertStatus(422);
        $this->postJson('/api/v1/portal/forms/pre-visit', ['answers' => ['smoker' => 'no', 'weight' => '62']], $headers)->assertCreated();

        $raw = DB::table('crm_form_submissions')->value('answers');
        $this->assertStringNotContainsString('smoker', $raw, 'answers are encrypted at rest');

        $svc->define($this->organization->id, 'pre-visit', 'Before your visit', [['key' => 'smoker', 'label' => 'Do you smoke?', 'type' => 'yesno', 'required' => true], ['key' => 'pregnant', 'label' => 'Pregnant?', 'type' => 'yesno']]);
        $this->assertSame(2, $form->fresh()->version);
        $old = CrmFormSubmission::query()->first();
        $this->assertSame(1, $old->form_version);
        $this->assertSame('weight', $old->answers['fields'][1]['key'], 'the old answer still carries the questions it was given');
    }

    public function test_a_form_with_bad_question_definitions_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        app(FormService::class)->define($this->organization->id, 'bad', 'Bad', [['key' => 'Bad Key', 'label' => 'x', 'type' => 'text']]);
    }
}
