<?php

namespace Tests\Feature\Icu;

use App\Models\Encounter;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Patient;
use App\Models\Workflow;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStep;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\SettingsService;
use Illuminate\Validation\ValidationException;

class IcuAdmissionWorkflowTest extends IcuTestCase
{
    public function test_direct_approve_fallback_when_approval_not_required(): void
    {
        app(SettingsService::class)->set('icu.admission_requires_approval', false);

        $request = $this->makeAdmissionRequest();

        $this->assertSame(IcuAdmissionRequest::STATUS_ACCEPTED, $request->status);
        $this->assertNotNull($request->accepted_at);
    }

    public function test_workflow_engine_approval_path_when_a_workflow_is_configured(): void
    {
        app(SettingsService::class)->set('icu.admission_requires_approval', true);

        $workflow = Workflow::create(['organization_id' => $this->organization->id, 'code' => 'icu_admission', 'name' => 'ICU Admission Approval', 'is_active' => true]);
        $step = WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Approval', 'is_final' => true]);
        WorkflowApprover::create(['workflow_step_id' => $step->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'super_admin']);

        $request = $this->makeAdmissionRequest();

        $this->assertNotNull($request->workflow_instance_id);
        $this->assertSame(IcuAdmissionRequest::STATUS_ACCEPTED, $request->status);
    }

    public function test_pending_approval_fallback_when_approval_required_but_no_workflow_configured(): void
    {
        app(SettingsService::class)->set('icu.admission_requires_approval', true);

        $request = $this->makeAdmissionRequest();

        $this->assertSame(IcuAdmissionRequest::STATUS_PENDING_APPROVAL, $request->status);

        $accepted = app(IcuAdmissionRequestService::class)->accept($request->fresh(), $this->user);

        $this->assertSame(IcuAdmissionRequest::STATUS_ACCEPTED, $accepted->status);
    }

    public function test_emergency_admission_requires_an_override_reason(): void
    {
        $this->expectException(ValidationException::class);

        app(IcuAdmissionRequestService::class)->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => Patient::factory()->create(['organization_id' => $this->organization->id])->id,
            'encounter_id' => Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id])->id,
            'is_emergency' => true,
        ], $this->user);
    }

    public function test_emergency_admission_with_a_reason_bypasses_approval_but_is_still_audited(): void
    {
        app(SettingsService::class)->set('icu.admission_requires_approval', true);

        $request = $this->makeAdmissionRequest(null, isEmergency: true);

        $this->assertSame(IcuAdmissionRequest::STATUS_ACCEPTED, $request->status);
        $this->assertTrue($request->is_emergency);
        $this->assertNotNull($request->override_reason);
        $this->assertNotNull($request->requested_by);
    }

    public function test_cancel_is_blocked_once_an_episode_already_exists(): void
    {
        $episode = $this->makeEpisode();
        $request = $episode->admissionRequest;

        $this->expectException(ValidationException::class);

        app(IcuAdmissionRequestService::class)->cancel($request, $this->user, 'changed my mind');
    }
}
