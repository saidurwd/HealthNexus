<?php

namespace App\Services\Ot;

use App\Events\Ot\SurgeryApproved;
use App\Events\Ot\SurgeryRequested;
use App\Models\ClinicalOrder;
use App\Models\Encounter;
use App\Models\Ot\OtSurgeryRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Services\SettingsService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Surgery request approval is the one place Phase 10 reuses the generic WorkflowEngine (decision
 * #11 of the plan) — mirrors IpdAdmissionRequestService byte-for-byte, including the direct-
 * approve fallback when no workflow is configured for the organization.
 */
class OtSurgeryRequestService
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly SettingsService $settings,
    ) {}

    public function create(array $data, User $user): OtSurgeryRequest
    {
        return DB::transaction(function () use ($data, $user) {
            $requiresApproval = $this->settings->get('ot.surgery_requires_approval', true);

            $request = OtSurgeryRequest::create([
                ...$data,
                'status' => $requiresApproval ? OtSurgeryRequest::STATUS_REQUESTED : OtSurgeryRequest::STATUS_APPROVED,
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);

            event(new SurgeryRequested($request));

            if (! $requiresApproval) {
                $request->update(['approved_by' => $user->id, 'approved_at' => now()]);

                return $request->fresh();
            }

            $workflow = Workflow::query()
                ->where('organization_id', $data['organization_id'])
                ->where('code', 'ot_surgery_request')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                $instance = $this->engine->start($workflow, $request, $user, [
                    'organization_id' => $data['organization_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'notes' => $data['indication'] ?? null,
                ]);
                $instance = $this->engine->submit($instance, $user);

                $request->update([
                    'workflow_instance_id' => $instance->id,
                    'status' => $instance->status === 'approved' ? OtSurgeryRequest::STATUS_APPROVED : OtSurgeryRequest::STATUS_PENDING_APPROVAL,
                ]);
            } else {
                $request->update(['status' => OtSurgeryRequest::STATUS_PENDING_APPROVAL]);
            }

            return $request->fresh();
        });
    }

    /**
     * Fans out from the existing ClinicalOrder mechanism, exactly like Lab/Radiology's
     * registerFromClinicalOrder() — called from CreateSurgeryRequestOnClinicalOrderCreated.
     */
    public function createFromClinicalOrder(Encounter $encounter, ClinicalOrder $order, User $user): OtSurgeryRequest
    {
        return $this->create([
            'organization_id' => $encounter->organization_id,
            'branch_id' => $encounter->branch_id,
            'patient_id' => $encounter->patient_id,
            'encounter_id' => $encounter->id,
            'clinical_order_id' => $order->id,
            'indication' => $order->notes,
            'priority' => $order->priority ?? 'routine',
        ], $user);
    }

    public function approve(OtSurgeryRequest $request, User $approver, ?string $note = null): OtSurgeryRequest
    {
        return DB::transaction(function () use ($request, $approver, $note) {
            if (! in_array($request->status, [OtSurgeryRequest::STATUS_REQUESTED, OtSurgeryRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            $instance = $request->workflowInstance;

            if ($instance) {
                $instance = $this->engine->approve($instance, $approver, $note);

                if ($instance->status === 'approved') {
                    $this->engine->complete($instance, $approver);
                    $request->update(['status' => OtSurgeryRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
                    event(new SurgeryApproved($request->fresh()));
                } else {
                    $request->update(['status' => OtSurgeryRequest::STATUS_PENDING_APPROVAL]);
                }
            } else {
                $request->update(['status' => OtSurgeryRequest::STATUS_APPROVED, 'approved_by' => $approver->id, 'approved_at' => now()]);
                event(new SurgeryApproved($request->fresh()));
            }

            return $request->fresh();
        });
    }

    public function reject(OtSurgeryRequest $request, User $approver, string $reason): OtSurgeryRequest
    {
        return DB::transaction(function () use ($request, $approver, $reason) {
            if (! in_array($request->status, [OtSurgeryRequest::STATUS_REQUESTED, OtSurgeryRequest::STATUS_PENDING_APPROVAL], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance) {
                $this->engine->reject($request->workflowInstance, $approver, $reason);
            }

            $request->update(['status' => OtSurgeryRequest::STATUS_REJECTED, 'rejected_at' => now(), 'rejection_reason' => $reason]);

            return $request->fresh();
        });
    }

    public function cancel(OtSurgeryRequest $request, User $user, string $reason): OtSurgeryRequest
    {
        return DB::transaction(function () use ($request, $user, $reason) {
            if ($request->surgery()->exists()) {
                throw ValidationException::withMessages(['request' => 'This request has already resulted in a surgical case and cannot be cancelled.']);
            }

            if (in_array($request->status, [OtSurgeryRequest::STATUS_CANCELLED, OtSurgeryRequest::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages(['status' => "This request is already '{$request->status}'."]);
            }

            if ($request->workflowInstance && ! $request->workflowInstance->isTerminal()) {
                $this->engine->cancel($request->workflowInstance, $user, $reason);
            }

            $request->update(['status' => OtSurgeryRequest::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);

            return $request->fresh();
        });
    }
}
