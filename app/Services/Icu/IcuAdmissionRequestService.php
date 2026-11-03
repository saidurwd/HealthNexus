<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUAdmissionAccepted;
use App\Events\Icu\ICUAdmissionRequested;
use App\Models\Icu\IcuAdmissionRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Services\SettingsService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ICU admission REQUEST is a direct-create entry point, not a ClinicalOrder fan-out — mirrors
 * IpdAdmissionRequestService byte-for-byte, including the direct-approve fallback when no
 * 'icu_admission' workflow is configured for the organization. Emergency admission (is_emergency=true)
 * skips the workflow step but still requires an explicit override_reason and is fully audited —
 * never a silent bypass (spec §6).
 */
class IcuAdmissionRequestService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly SettingsService $settings,
    ) {}

    public function create(array $data, User $user): IcuAdmissionRequest
    {
        return DB::transaction(function () use ($data, $user) {
            $isEmergency = (bool) ($data['is_emergency'] ?? false);

            if ($isEmergency && empty($data['override_reason'])) {
                throw ValidationException::withMessages(['override_reason' => 'An explicit reason is required for an emergency ICU admission override.']);
            }

            $requiresApproval = ! $isEmergency && $this->settings->get('icu.admission_requires_approval', true);

            $request = IcuAdmissionRequest::create([
                ...$data,
                'is_emergency' => $isEmergency,
                'status' => $requiresApproval ? IcuAdmissionRequest::STATUS_REQUESTED : IcuAdmissionRequest::STATUS_ACCEPTED,
                'requested_by' => $user->id,
            ]);

            event(new ICUAdmissionRequested($request));

            if (! $requiresApproval) {
                $request->update(['accepted_by' => $user->id, 'accepted_at' => now()]);
                event(new ICUAdmissionAccepted($request->fresh()));

                return $request->fresh();
            }

            $workflow = Workflow::query()
                ->where('organization_id', $data['organization_id'])
                ->where('code', 'icu_admission')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                $instance = $this->engine->start($workflow, $request, $user, [
                    'organization_id' => $data['organization_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'notes' => $data['admission_reason'] ?? null,
                ]);
                $instance = $this->engine->submit($instance, $user);

                $request->update([
                    'workflow_instance_id' => $instance->id,
                    'status' => $instance->status === 'approved' ? IcuAdmissionRequest::STATUS_ACCEPTED : IcuAdmissionRequest::STATUS_PENDING_APPROVAL,
                ]);

                if ($request->status === IcuAdmissionRequest::STATUS_ACCEPTED) {
                    $request->update(['accepted_by' => $user->id, 'accepted_at' => now()]);
                    event(new ICUAdmissionAccepted($request->fresh()));
                }
            } else {
                // No approval workflow configured for this organization — fall back to a direct,
                // audited "pending_approval" state a coordinator can still explicitly accept/reject.
                $request->update(['status' => IcuAdmissionRequest::STATUS_PENDING_APPROVAL]);
            }

            return $request->fresh();
        });
    }

    public function accept(IcuAdmissionRequest $request, User $approver, ?string $note = null): IcuAdmissionRequest
    {
        return DB::transaction(function () use ($request, $approver, $note) {
            if (! in_array($request->status, [IcuAdmissionRequest::STATUS_REQUESTED, IcuAdmissionRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            $instance = $request->workflowInstance;

            if ($instance) {
                $instance = $this->engine->approve($instance, $approver, $note);

                if ($instance->status === 'approved') {
                    $this->engine->complete($instance, $approver);
                    $request->update(['status' => IcuAdmissionRequest::STATUS_ACCEPTED, 'accepted_by' => $approver->id, 'accepted_at' => now()]);
                    event(new ICUAdmissionAccepted($request->fresh()));
                } else {
                    $request->update(['status' => IcuAdmissionRequest::STATUS_PENDING_APPROVAL]);
                }
            } else {
                $request->update(['status' => IcuAdmissionRequest::STATUS_ACCEPTED, 'accepted_by' => $approver->id, 'accepted_at' => now()]);
                event(new ICUAdmissionAccepted($request->fresh()));
            }

            return $request->fresh();
        });
    }

    public function reject(IcuAdmissionRequest $request, User $approver, string $reason): IcuAdmissionRequest
    {
        return DB::transaction(function () use ($request, $approver, $reason) {
            if (! in_array($request->status, [IcuAdmissionRequest::STATUS_REQUESTED, IcuAdmissionRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance) {
                $this->engine->reject($request->workflowInstance, $approver, $reason);
            }

            $request->update(['status' => IcuAdmissionRequest::STATUS_REJECTED, 'rejected_at' => now(), 'rejection_reason' => $reason]);

            return $request->fresh();
        });
    }

    public function cancel(IcuAdmissionRequest $request, User $user, string $reason): IcuAdmissionRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            if ($request->episode()->exists()) {
                throw ValidationException::withMessages(['request' => 'This request has already resulted in an ICU episode and cannot be cancelled.']);
            }

            if (in_array($request->status, [IcuAdmissionRequest::STATUS_CANCELLED, IcuAdmissionRequest::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance && ! $request->workflowInstance->isTerminal()) {
                $this->engine->cancel($request->workflowInstance, $user, $reason);
            }

            $request->update(['status' => IcuAdmissionRequest::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);

            return $request->fresh();
        });
    }
}
