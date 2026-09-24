<?php

namespace Tests\Feature\Nursing;

use App\Models\Diagnosis;
use App\Models\Nursing\NursingNote;
use App\Models\Nursing\NursingTask;
use App\Services\Nursing\NursingAssessmentService;
use App\Services\Nursing\NursingCarePlanService;
use App\Services\Nursing\NursingDischargeChecklistService;
use App\Services\Nursing\NursingEscalationService;
use App\Services\Nursing\NursingFluidBalanceService;
use App\Services\Nursing\NursingHandoverService;
use App\Services\Nursing\NursingNoteService;
use App\Services\Nursing\NursingObservationService;
use App\Services\Nursing\NursingTaskService;
use App\Services\Nursing\NursingVitalService;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

class ClinicalRecordsTest extends NursingTestCase
{
    public function test_finalized_note_cannot_be_edited_only_addended(): void
    {
        $episode = $this->makeEpisode();
        $notes = app(NursingNoteService::class);
        $note = $notes->create($episode, ['note_type' => 'narrative', 'content' => 'original'], $this->user);
        $notes->finalize($note, $this->user);

        try {
            $notes->update($note->fresh(), ['content' => 'silently changed']);
            $this->fail('Editing a finalized note must be refused.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $notes->addAddendum($note->fresh(), 'extra info', $this->user);

        $this->assertSame('original', NursingNote::find($note->id)->content);
        $this->assertDatabaseHas('nursing_note_amendments', ['note_id' => $note->id, 'content' => 'extra info']);
    }

    public function test_addendum_requires_a_finalized_note(): void
    {
        $note = app(NursingNoteService::class)->create($this->makeEpisode(), ['note_type' => 'narrative', 'content' => 'x'], $this->user);

        $this->expectException(ValidationException::class);
        app(NursingNoteService::class)->addAddendum($note, 'y', $this->user);
    }

    public function test_task_cannot_be_completed_twice(): void
    {
        $tasks = app(NursingTaskService::class);
        $task = $tasks->create($this->makeEpisode(), ['task_type' => 'Reposition', 'due_at' => now()->addHour()]);
        $tasks->complete($task, $this->user, 'done');

        $this->expectException(ValidationException::class);
        $tasks->complete($task->fresh(), $this->user);
    }

    public function test_overdue_job_flags_tasks_once(): void
    {
        $task = app(NursingTaskService::class)->create($this->makeEpisode(), ['task_type' => 'Obs', 'due_at' => now()->subHour()]);

        \App\Jobs\Nursing\DetectOverdueNursingTasks::dispatchSync();
        \App\Jobs\Nursing\DetectOverdueNursingTasks::dispatchSync();

        $this->assertSame(NursingTask::STATUS_OVERDUE, $task->fresh()->status);
    }

    public function test_handover_cannot_be_acknowledged_twice(): void
    {
        $handovers = app(NursingHandoverService::class);
        $handover = $handovers->prepare($this->makeEpisode(), $this->user);
        $incoming = $this->makeNurse();
        $handovers->finalize($handover, $incoming);
        $handovers->acknowledge($handover->fresh(), $incoming);

        $this->assertNotEmpty($handover->fresh('items')->items);

        $this->expectException(ValidationException::class);
        $handovers->acknowledge($handover->fresh(), $incoming);
    }

    public function test_escalation_lifecycle_and_never_creates_a_diagnosis(): void
    {
        $service = app(NursingEscalationService::class);
        $escalation = $service->create($this->makeEpisode(), ['concern' => 'Patient drowsy', 'recipient_type' => 'charge_nurse', 'severity' => 'high'], $this->user);
        $service->acknowledge($escalation, $this->user);
        $service->resolve($escalation->fresh(), $this->user, 'Reviewed by physician');

        $this->assertNotNull($escalation->fresh()->resolved_at);
        $this->assertSame(0, Diagnosis::count());
        $this->assertDatabaseCount('nursing_diagnoses', 0);

        $this->expectException(ValidationException::class);
        $service->resolve($escalation->fresh(), $this->user, 'again');
    }

    public function test_discharge_checklist_requires_every_item_and_does_not_touch_ipd_discharge(): void
    {
        $checklists = app(NursingDischargeChecklistService::class);
        $checklist = $checklists->create($this->makeEpisode());

        try {
            $checklists->complete($checklist, $this->user);
            $this->fail('Incomplete checklist must not complete.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $checklists->updateItem($checklist, [
            'education_completed' => true, 'medication_education_completed' => true, 'devices_removed' => true,
            'belongings_confirmed' => true, 'follow_up_instructions_given' => true,
        ]);
        $done = $checklists->complete($checklist->fresh(), $this->user);

        $this->assertSame('completed', $done->status);
        $this->assertDatabaseCount('ipd_discharge_requests', 0);
    }

    public function test_care_plan_chain_uses_nursing_diagnosis_not_physician_diagnosis(): void
    {
        $service = app(NursingCarePlanService::class);
        $plan = $service->create($this->makeEpisode(), $this->user);
        $diagnosis = $service->addDiagnosis($plan, ['diagnosis_text' => 'Impaired mobility'], $this->user);
        $goal = $service->addGoal($plan, ['nursing_diagnosis_id' => $diagnosis->id, 'goal_text' => 'Walk unaided']);
        $service->addIntervention($plan, ['goal_id' => $goal->id, 'intervention_type' => 'Mobility assistance']);
        $service->evaluate($goal, 'met');
        $service->complete($plan);

        $this->assertSame('completed', $plan->fresh()->status);
        $this->assertSame(0, Diagnosis::count());

        $this->expectException(ValidationException::class);
        $service->complete($plan->fresh());
    }

    public function test_assessment_locks_after_finalize(): void
    {
        $service = app(NursingAssessmentService::class);
        $assessment = $service->create($this->makeEpisode(), ['assessment_type' => 'initial', 'sections' => ['general' => 'alert']], $this->user);
        $service->finalize($assessment, $this->user);

        $this->expectException(ValidationException::class);
        $service->update($assessment->fresh(), ['sections' => ['general' => 'changed']]);
    }

    public function test_vitals_go_through_the_shared_vital_signs_table(): void
    {
        $episode = $this->makeEpisode();
        app(NursingVitalService::class)->record($episode, ['pulse_rate' => 80, 'systolic' => 120, 'diastolic' => 80], $this->user);
        app(NursingVitalService::class)->record($episode, ['pulse_rate' => 90], $this->user);

        $this->assertSame(2, \App\Models\VitalSign::where('encounter_id', $episode->encounter_id)->count());
    }

    public function test_observation_threshold_breach_raises_an_alert_never_a_diagnosis(): void
    {
        app(SettingsService::class)->set('nursing.observation_thresholds', ['blood_glucose' => ['low' => 70, 'high' => 200]]);
        $episode = $this->makeEpisode();
        $service = app(NursingObservationService::class);

        $service->record($episode, ['observation_type' => 'blood_glucose', 'value' => '110'], $this->user);
        $this->assertDatabaseCount('nursing_alerts', 0);

        $original = $service->record($episode, ['observation_type' => 'blood_glucose', 'value' => '350'], $this->user);
        $this->assertDatabaseCount('nursing_alerts', 1);
        $this->assertSame(0, Diagnosis::count());

        $corrected = $service->correct($original, ['value' => '150'], $this->user);
        $this->assertSame('corrected', $corrected->status);
        $this->assertSame('350', $original->fresh()->value);
    }

    public function test_fluid_balance_is_computed_not_stored(): void
    {
        $episode = $this->makeEpisode();
        $service = app(NursingFluidBalanceService::class);
        $service->recordIntake($episode, ['category' => 'oral', 'amount' => 500], $this->user);
        $service->recordOutput($episode, ['category' => 'urine', 'amount' => 200], $this->user);

        $balance = $service->netBalance($episode, now()->subHour(), now()->addHour());
        $this->assertSame(300.0, $balance['net_balance']);
    }
}
