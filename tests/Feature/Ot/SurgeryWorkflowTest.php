<?php

namespace Tests\Feature\Ot;

use App\Models\Billing\BillingCharge;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Ot\OtSurgicalTeam;
use App\Models\Patient;
use App\Services\Ot\OtChargeService;
use App\Services\Ot\OtConsentService;
use App\Services\Ot\OtImplantService;
use App\Services\Ot\OtOperativeNoteService;
use App\Services\Ot\OtPreoperativeAssessmentService;
use App\Services\Ot\OtPreoperativeChecklistService;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtRecoveryService;
use App\Services\Ot\OtSchedulingService;
use App\Services\Ot\OtSpecimenService;
use App\Services\Ot\OtSurgeryRequestService;
use App\Services\Ot\OtSurgeryService;
use App\Services\Ot\OtSurgicalCountService;
use App\Services\Ot\OtSurgicalSafetyChecklistService;
use App\Services\Ot\OtSurgicalTeamService;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

class SurgeryWorkflowTest extends OtTestCase
{
    public function test_surgery_request_direct_approve_fallback_when_no_workflow_configured(): void
    {
        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);

        app(SettingsService::class)->set('ot.surgery_requires_approval', false);

        $request = app(OtSurgeryRequestService::class)->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
        ], $this->user);

        $this->assertSame(OtSurgeryRequest::STATUS_APPROVED, $request->status);
    }

    public function test_surgery_request_requires_approval_when_configured_and_can_be_created_into_a_case(): void
    {
        app(SettingsService::class)->set('ot.surgery_requires_approval', true);

        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);

        $requests = app(OtSurgeryRequestService::class);
        $request = $requests->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
        ], $this->user);

        $this->assertSame(OtSurgeryRequest::STATUS_PENDING_APPROVAL, $request->status);

        $approved = $requests->approve($request, $this->user);
        $this->assertSame(OtSurgeryRequest::STATUS_APPROVED, $approved->status);

        $surgery = app(OtSurgeryService::class)->createFromRequest($approved, $this->user);
        $this->assertSame($patient->id, $surgery->patient_id);

        $this->expectException(ValidationException::class);
        $requests->cancel($approved->fresh(), $this->user, 'changed mind');
    }

    public function test_schedule_reschedule_postpone_cancel_lifecycle(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        $scheduling = app(OtSchedulingService::class);

        $start = now()->addDay()->setTime(9, 0);
        $end = now()->addDay()->setTime(11, 0);

        $scheduled = $scheduling->schedule($surgery, ['theatre_id' => $theatre->id, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end], $this->user);
        $this->assertSame(OtSurgery::STATUS_SCHEDULED, $scheduled->status);

        $rescheduled = $scheduling->reschedule($scheduled->fresh(), [
            'scheduled_start_at' => $start->copy()->addHours(2), 'scheduled_end_at' => $end->copy()->addHours(2),
        ], $this->user);
        $this->assertSame(OtSurgery::STATUS_SCHEDULED, $rescheduled->status);

        $postponed = $scheduling->postpone($rescheduled->fresh(), 'no blood available', $this->user);
        $this->assertSame(OtSurgery::STATUS_POSTPONED, $postponed->status);

        $cancelled = $scheduling->cancel($postponed->fresh(), 'patient refused', $this->user);
        $this->assertSame(OtSurgery::STATUS_CANCELLED, $cancelled->status);

        // Re-cancelling an already-cancelled surgery is a safe no-op (same-status transitions
        // are idempotent, matching IpdAdmissionLifecycleService's established convention) — it
        // must never advance the surgery past 'cancelled' or throw.
        $recancelled = $scheduling->cancel($cancelled->fresh(), 'again', $this->user);
        $this->assertSame(OtSurgery::STATUS_CANCELLED, $recancelled->status);
    }

    public function test_team_assignment_is_append_only(): void
    {
        $surgery = $this->makeDaySurgery();
        $surgeon1 = $this->makeSurgeon();
        $surgeon2 = $this->makeSurgeon();
        $team = app(OtSurgicalTeamService::class);

        $first = $team->assign($surgery, $surgeon1, OtSurgicalTeam::ROLE_PRIMARY_SURGEON, $this->user);
        $second = $team->assign($surgery, $surgeon2, OtSurgicalTeam::ROLE_PRIMARY_SURGEON, $this->user);

        $this->assertNotNull($first->fresh()->removed_at);
        $this->assertNull($second->fresh()->removed_at);
        $this->assertSame($surgeon2->id, $surgery->fresh()->primary_surgeon_id);
        $this->assertDatabaseCount('ot_surgical_team', 2);
    }

    public function test_full_reception_through_completion_requires_sign_in_and_sign_out(): void
    {
        $surgery = $this->makeDaySurgery();
        $theatre = $this->makeTheatre();
        app(OtSchedulingService::class)->schedule($surgery, [
            'theatre_id' => $theatre->id, 'scheduled_start_at' => now(), 'scheduled_end_at' => now()->addHours(2),
        ], $this->user);
        app(OtPreoperativeChecklistService::class)->initialize($surgery);

        $surgery = $surgery->fresh();
        app(OtReceptionService::class)->receive($surgery, $this->user);
        $surgery = $surgery->fresh();

        $reception = app(OtReceptionService::class);

        try {
            $reception->start($surgery, $this->user);
            $this->fail('Starting without a completed Sign In checklist must be refused.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $safety = app(OtSurgicalSafetyChecklistService::class);
        $signIn = $safety->initializePhase($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_IN);
        foreach ($signIn->items as $item) {
            $safety->completeItem($item, $this->user, 'confirmed');
        }
        $safety->completePhase($signIn, $this->user);

        $surgery = $reception->start($surgery->fresh(), $this->user);
        $this->assertSame(OtSurgery::STATUS_IN_PROCEDURE, $surgery->status);

        try {
            $reception->complete($surgery, $this->user);
            $this->fail('Completing without a completed Sign Out checklist must be refused.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $signOut = $safety->initializePhase($surgery, OtSurgicalSafetyChecklist::PHASE_SIGN_OUT);
        foreach ($signOut->items as $item) {
            $safety->completeItem($item, $this->user, 'confirmed');
        }
        $safety->completePhase($signOut, $this->user);

        $surgery = $reception->complete($surgery->fresh(), $this->user);
        $this->assertSame(OtSurgery::STATUS_PROCEDURE_COMPLETED, $surgery->status);
    }

    public function test_preoperative_checklist_blocks_required_item_without_response_or_exception(): void
    {
        $surgery = $this->makeDaySurgery();
        $checklists = app(OtPreoperativeChecklistService::class);
        $checklist = $checklists->initialize($surgery);
        $requiredItem = $checklist->items->firstWhere('is_required', true);

        $this->expectException(ValidationException::class);
        $checklists->completeItem($requiredItem, $this->user, null, null);
    }

    public function test_consent_reuses_patient_consent_model(): void
    {
        $surgery = $this->makeDaySurgery();
        $consent = app(OtConsentService::class)->grant($surgery, $this->user, 'patient agrees', 'Jane Witness', 'en');

        $this->assertSame('surgical_procedure', $consent->consent_type);
        $this->assertSame('Jane Witness', $consent->witness_name);
        $this->assertSame($consent->id, $surgery->fresh()->consent_id);
        $this->assertDatabaseCount('ot_surgery_requests', 0);
    }

    public function test_preoperative_assessment_locks_after_finalize(): void
    {
        $surgery = $this->makeDaySurgery();
        $service = app(OtPreoperativeAssessmentService::class);
        $assessment = $service->create($surgery, ['sections' => ['airway' => 'clear']], $this->user);
        $service->finalize($assessment, $this->user);

        $this->expectException(ValidationException::class);
        $service->update($assessment->fresh(), ['sections' => ['airway' => 'changed']]);
    }

    public function test_operative_note_locks_after_finalize_and_addendum_preserves_original(): void
    {
        $surgery = $this->makeDaySurgery();
        $notes = app(OtOperativeNoteService::class);
        $note = $notes->create($surgery, ['findings' => 'normal anatomy'], $this->user);
        $notes->finalize($note, $this->user);

        try {
            $notes->update($note->fresh(), ['findings' => 'changed']);
            $this->fail('A finalized operative note must not be silently editable.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $notes->addAddendum($note->fresh(), 'late finding noted', 'addendum reason', $this->user);

        $this->assertSame('normal anatomy', $note->fresh()->findings);
        $this->assertDatabaseHas('ot_operative_note_amendments', ['operative_note_id' => $note->id, 'content' => 'late finding noted']);
    }

    public function test_surgical_counts_are_append_only_and_discrepancies_are_recorded_not_corrected(): void
    {
        $surgery = $this->makeDaySurgery();
        $counts = app(OtSurgicalCountService::class);

        $counts->record($surgery, ['count_type' => 'swab', 'stage' => 'initial', 'count_value' => '10'], $this->user);
        $counts->record($surgery, ['count_type' => 'swab', 'stage' => 'final', 'count_value' => '9', 'discrepancy' => true, 'discrepancy_notes' => 'one missing, X-ray ordered'], $this->user);

        $this->assertDatabaseCount('ot_surgical_counts', 2);
        $this->assertDatabaseHas('ot_surgical_counts', ['surgery_id' => $surgery->id, 'discrepancy' => 1]);
    }

    public function test_specimen_collection_without_lab_handoff_creates_no_clinical_order(): void
    {
        $surgery = $this->makeDaySurgery();
        $specimen = app(OtSpecimenService::class)->collect($surgery, ['specimen_type' => 'appendix'], $this->user, false);

        $this->assertNull($specimen->clinical_order_id);
        $this->assertNotEmpty($specimen->specimen_number);
    }

    public function test_specimen_collection_with_lab_handoff_creates_a_laboratory_clinical_order(): void
    {
        $surgery = $this->makeDaySurgery();
        $specimen = app(OtSpecimenService::class)->collect($surgery, ['specimen_type' => 'tonsil'], $this->user, true);

        $this->assertNotNull($specimen->clinical_order_id);
        $this->assertDatabaseHas('clinical_orders', ['id' => $specimen->clinical_order_id, 'order_type' => 'laboratory']);
    }

    public function test_implant_charge_is_idempotent_per_implant_row(): void
    {
        $surgery = $this->makeDaySurgery();
        $implant = app(OtImplantService::class)->record($surgery, ['implant_name' => 'hip implant'], $this->user);

        // Re-charging the same implant row must not create a second BillingCharge.
        $charges = app(OtChargeService::class);
        $charges->chargeImplant($implant, $this->user);
        $charges->chargeImplant($implant, $this->user);

        $this->assertLessThanOrEqual(1, BillingCharge::where('source_id', $implant->id)->where('source_type', $implant->getMorphClass())->count());
    }

    public function test_day_surgery_recovery_discharges_without_any_ipd_involvement(): void
    {
        $surgery = $this->progressToCompleted($this->makeDaySurgery());
        $recovery = app(OtRecoveryService::class)->admitToRecovery($surgery);
        app(OtRecoveryService::class)->updateAssessment($recovery, ['discharge_criteria_met' => true]);
        app(OtRecoveryService::class)->markReadyForTransfer($recovery->fresh());

        $completed = app(OtRecoveryService::class)->completeTransfer($recovery->fresh(), 'discharge', $this->user);

        $this->assertSame('discharged', $completed->status);
        $this->assertDatabaseCount('ipd_admissions', 0);
    }

    public function test_ipd_admitted_patient_recovery_transfer_reactivates_the_existing_admission(): void
    {
        $surgery = $this->makeSurgery(withAdmission: true);
        $admissionId = $surgery->admission_id;
        $surgery = $this->progressToCompleted($surgery);

        $recovery = app(OtRecoveryService::class)->admitToRecovery($surgery);
        app(OtRecoveryService::class)->updateAssessment($recovery, ['discharge_criteria_met' => true]);
        app(OtRecoveryService::class)->markReadyForTransfer($recovery->fresh());
        app(OtRecoveryService::class)->completeTransfer($recovery->fresh(), 'ward', $this->user);

        $this->assertSame('active', IpdAdmission::find($admissionId)->status);
    }

    public function test_recovery_cannot_be_marked_ready_without_discharge_criteria(): void
    {
        $surgery = $this->progressToCompleted($this->makeDaySurgery());
        $recovery = app(OtRecoveryService::class)->admitToRecovery($surgery);

        $this->expectException(ValidationException::class);
        app(OtRecoveryService::class)->markReadyForTransfer($recovery);
    }
}
