<?php

namespace App\Services\Ipd;

use App\Events\Ipd\AdmissionApproved;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Services\SettingsService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admission requests are the one place this module reuses the generic WorkflowEngine, per the
 * spec's explicit instruction not to build a second approval framework — mirrors
 * PatientAmendmentService's start()+submit() on create, approve()/complete() on approval.
 */
class IpdAdmissionRequestService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly SettingsService $settings,
    ) {}

    public function create(array $data, User $user): IpdAdmissionRequest
    {
        return DB::transaction(function () use ($data, $user) {
            $requiresApproval = $this->settings->get('ipd.admission_requires_approval', true);

            $request = IpdAdmissionRequest::create([
                ...$data,
                'status' => $requiresApproval ? IpdAdmissionRequest::STATUS_REQUESTED : IpdAdmissionRequest::STATUS_APPROVED,
                'requested_by' => $user->id,
            ]);

            if (! $requiresApproval) {
                $request->update(['approved_by' => $user->id, 'approved_at' => now()]);

                return $request->fresh();
            }

            $workflow = Workflow::query()
                ->where('company_id', $data['company_id'])
                ->where('code', 'ipd_admission')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                $instance = $this->engine->start($workflow, $request, $user, [
                    'company_id' => $data['company_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'notes' => $data['reason'] ?? null,
                ]);
                $instance = $this->engine->submit($instance, $user);

                $request->update([
                    'workflow_instance_id' => $instance->id,
                    'status' => $instance->status === 'approved' ? IpdAdmissionRequest::STATUS_APPROVED : IpdAdmissionRequest::STATUS_PENDING_APPROVAL,
                ]);
            } else {
                // No approval workflow configured for this company — fall back to a direct,
                // audited "requested" state a coordinator can still explicitly approve/reject.
                $request->update(['status' => IpdAdmissionRequest::STATUS_PENDING_APPROVAL]);
            }

            return $request->fresh();
        });
    }

    public function approve(IpdAdmissionRequest $request, User $approver, ?string $note = null): IpdAdmissionRequest
    {
        return DB::transaction(function () use ($request, $approver, $note) {
            if (! in_array($request->status, [IpdAdmissionRequest::STATUS_REQUESTED, IpdAdmissionRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            $instance = $request->workflowInstance;

            if ($instance) {
                $instance = $this->engine->approve($instance, $approver, $note);

                if ($instance->status === 'approved') {
                    $this->engine->complete($instance, $approver);
                    $request->update(['status' => IpdAdmissionRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
                    event(new AdmissionApproved($request->fresh()));
                } else {
                    $request->update(['status' => IpdAdmissionRequest::STATUS_PENDING_APPROVAL]);
                }
            } else {
                $request->update(['status' => IpdAdmissionRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
                event(new AdmissionApproved($request->fresh()));
            }

            return $request->fresh();
        });
    }

    public function reject(IpdAdmissionRequest $request, User $approver, string $reason): IpdAdmissionRequest
    {
        return DB::transaction(function () use ($request, $approver, $reason) {
            if (! in_array($request->status, [IpdAdmissionRequest::STATUS_REQUESTED, IpdAdmissionRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance) {
                $this->engine->reject($request->workflowInstance, $approver, $reason);
            }

            $request->update(['status' => IpdAdmissionRequest::STATUS_REJECTED, 'rejected_at' => now(), 'rejection_reason' => $reason]);

            return $request->fresh();
        });
    }

    public function cancel(IpdAdmissionRequest $request, User $user, string $reason): IpdAdmissionRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            if ($request->admission()->exists()) {
                throw ValidationException::withMessages(['request' => 'This request has already resulted in an admission and cannot be cancelled.']);
            }

            if (in_array($request->status, [IpdAdmissionRequest::STATUS_CANCELLED, IpdAdmissionRequest::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance && ! $request->workflowInstance->isTerminal()) {
                $this->engine->cancel($request->workflowInstance, $user, $reason);
            }

            $request->update(['status' => IpdAdmissionRequest::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);

            return $request->fresh();
        });
    }
}
