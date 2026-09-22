<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Generic sequential approval engine: Draft -> Submitted/Pending Approval -> Approved ->
 * Completed, with Reject/Return/Cancel as side exits. A step is satisfied by any one of its
 * configured WorkflowApprover rows (role, specific user, or department membership) — this reads
 * as "any eligible approver" per step rather than full parallel/quorum approval, which keeps the
 * engine simple while still covering the common real-world case ("any billing supervisor").
 *
 * Any future module attaches its own record via WorkflowInstance's polymorphic subject_type/
 * subject_id — this engine has no knowledge of what it's approving.
 */
class WorkflowEngine
{
    /**
     * @param  array{company_id?:int|null,branch_id?:int|null,notes?:string|null}  $context
     */
    public function start(Workflow $workflow, Model $subject, User $initiator, array $context = []): WorkflowInstance
    {
        return DB::transaction(function () use ($workflow, $subject, $initiator, $context) {
            return WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'company_id' => $context['company_id'] ?? null,
                'branch_id' => $context['branch_id'] ?? null,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'status' => 'draft',
                'initiated_by' => $initiator->id,
                'notes' => $context['notes'] ?? null,
            ]);
        });
    }

    public function submit(WorkflowInstance $instance, User $user): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $user) {
            $this->assertStatus($instance, ['draft']);

            $firstStep = $instance->workflow->firstStep();

            $instance->update([
                'status' => $firstStep ? 'pending_approval' : 'approved',
                'current_step_id' => $firstStep?->id,
                'submitted_at' => now(),
            ]);

            $this->recordAction($instance, WorkflowAction::SUBMIT, $user, null, $firstStep?->id);

            return $instance->refresh();
        });
    }

    public function approve(WorkflowInstance $instance, User $approver, ?string $note = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $approver, $note) {
            $this->assertStatus($instance, ['pending_approval']);

            if (! $this->canAct($instance, $approver)) {
                throw ValidationException::withMessages(['approver' => 'You are not an eligible approver for this step.']);
            }

            $currentStepId = $instance->current_step_id;
            $currentStep = $instance->currentStep;
            $nextStep = $currentStep?->nextStep();

            $instance->update([
                'status' => $nextStep ? 'pending_approval' : 'approved',
                'current_step_id' => $nextStep?->id,
            ]);

            $this->recordAction($instance, WorkflowAction::APPROVE, $approver, $note, $currentStepId);

            return $instance->refresh();
        });
    }

    public function reject(WorkflowInstance $instance, User $approver, string $reason): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $approver, $reason) {
            $this->assertStatus($instance, ['pending_approval']);

            if (! $this->canAct($instance, $approver)) {
                throw ValidationException::withMessages(['approver' => 'You are not an eligible approver for this step.']);
            }

            $stepId = $instance->current_step_id;

            $instance->update(['status' => 'rejected', 'current_step_id' => null]);

            $this->recordAction($instance, WorkflowAction::REJECT, $approver, $reason, $stepId);

            return $instance->refresh();
        });
    }

    /**
     * Sends the instance back to Draft for revision by whoever initiated it.
     */
    public function returnForRevision(WorkflowInstance $instance, User $approver, string $reason): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $approver, $reason) {
            $this->assertStatus($instance, ['pending_approval']);

            if (! $this->canAct($instance, $approver)) {
                throw ValidationException::withMessages(['approver' => 'You are not an eligible approver for this step.']);
            }

            $stepId = $instance->current_step_id;

            $instance->update(['status' => 'returned', 'current_step_id' => null, 'submitted_at' => null]);

            $this->recordAction($instance, WorkflowAction::RETURN_, $approver, $reason, $stepId);

            return $instance->refresh();
        });
    }

    /**
     * Marks a fully-approved instance as completed once the consuming module has actually
     * carried out the approved action (e.g. the discount was applied, the refund was posted).
     */
    public function complete(WorkflowInstance $instance, User $user): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $user) {
            $this->assertStatus($instance, ['approved']);

            $instance->update(['status' => 'completed', 'completed_at' => now()]);

            $this->recordAction($instance, WorkflowAction::COMPLETE, $user);

            return $instance->refresh();
        });
    }

    public function cancel(WorkflowInstance $instance, User $user, ?string $reason = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $user, $reason) {
            $this->assertStatus($instance, ['draft', 'returned', 'pending_approval']);

            $instance->update(['status' => 'cancelled', 'current_step_id' => null]);

            $this->recordAction($instance, WorkflowAction::CANCEL, $user, $reason);

            return $instance->refresh();
        });
    }

    public function canAct(WorkflowInstance $instance, User $user): bool
    {
        if (! $instance->isPendingApproval() || $instance->current_step_id === null) {
            return false;
        }

        return $instance->currentStep->approvers
            ->contains(fn ($approver) => $approver->isEligible($user));
    }

    /**
     * @return Collection<int, WorkflowInstance>
     */
    public function pendingApprovalsFor(User $user): Collection
    {
        return WorkflowInstance::query()
            ->where('status', 'pending_approval')
            ->whereNotNull('current_step_id')
            ->with(['currentStep.approvers', 'workflow', 'initiatedBy'])
            ->get()
            ->filter(fn (WorkflowInstance $instance) => $this->canAct($instance, $user))
            ->values();
    }

    private function assertStatus(WorkflowInstance $instance, array $allowed): void
    {
        if (! in_array($instance->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "This action is not allowed while the instance is in '{$instance->status}' status.",
            ]);
        }
    }

    private function recordAction(WorkflowInstance $instance, string $action, User $user, ?string $note = null, ?int $stepId = null): WorkflowAction
    {
        return WorkflowAction::create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $stepId,
            'action' => $action,
            'performed_by' => $user->id,
            'note' => $note,
        ]);
    }
}
