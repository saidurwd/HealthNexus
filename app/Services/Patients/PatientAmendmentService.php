<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\PatientAmendment;
use App\Models\User;
use App\Models\Workflow;
use App\Services\SettingsService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;

/**
 * Routes sensitive patient-field corrections through the generic WorkflowEngine (Draft ->
 * Submitted -> Pending Approval -> Approved -> Applied/Rejected) instead of writing them
 * straight to the patient record. Which fields count as "sensitive" is configurable via the
 * patients.amendment_sensitive_fields setting.
 */
class PatientAmendmentService
{
    public function __construct(
        private WorkflowEngine $engine,
        private PatientTimelineService $timeline,
        private SettingsService $settings,
    ) {}

    public function requiresAmendment(array $changedFields): bool
    {
        $sensitive = $this->settings->get('patients.amendment_sensitive_fields', []);

        return (bool) array_intersect($changedFields, $sensitive);
    }

    public function request(Patient $patient, array $proposedChanges, string $reason, User $user): PatientAmendment
    {
        return DB::transaction(function () use ($patient, $proposedChanges, $reason, $user) {
            $original = [];
            foreach (array_keys($proposedChanges) as $field) {
                $original[$field] = $patient->{$field};
            }

            $amendment = PatientAmendment::create([
                'company_id' => $patient->company_id,
                'patient_id' => $patient->id,
                'proposed_changes' => $proposedChanges,
                'original_values' => $original,
                'reason' => $reason,
                'status' => 'draft',
                'requested_by' => $user->id,
            ]);

            $workflow = Workflow::query()
                ->where('company_id', $patient->company_id)
                ->where('code', 'patient_amendment')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                $instance = $this->engine->start($workflow, $amendment, $user, [
                    'company_id' => $patient->company_id,
                    'branch_id' => $patient->branchRegistrations()->value('branch_id'),
                    'notes' => $reason,
                ]);
                $instance = $this->engine->submit($instance, $user);

                $amendment->update([
                    'workflow_instance_id' => $instance->id,
                    'status' => $instance->status === 'approved' ? 'approved' : 'pending_approval',
                ]);
            } else {
                // No approval workflow configured for this company — fall back to a direct,
                // audited apply rather than leaving the amendment stuck with nobody able to act.
                $amendment->update(['status' => 'submitted']);
                $this->apply($amendment, $user);
            }

            $this->timeline->record($patient, 'AMENDMENT_REQUESTED', "Amendment requested: {$reason}", $amendment, $user);

            return $amendment->fresh();
        });
    }

    public function approve(PatientAmendment $amendment, User $approver, ?string $note = null): PatientAmendment
    {
        $instance = $amendment->workflowInstance;

        if (! $instance) {
            throw new \RuntimeException('This amendment has no approval workflow to act on.');
        }

        return DB::transaction(function () use ($amendment, $instance, $approver, $note) {
            $instance = $this->engine->approve($instance, $approver, $note);

            if ($instance->status === 'approved') {
                $this->apply($amendment, $approver);
                $this->engine->complete($instance, $approver);
            } else {
                $amendment->update(['status' => 'pending_approval']);
            }

            return $amendment->fresh();
        });
    }

    public function reject(PatientAmendment $amendment, User $approver, string $reason): PatientAmendment
    {
        $instance = $amendment->workflowInstance;

        if (! $instance) {
            throw new \RuntimeException('This amendment has no approval workflow to act on.');
        }

        return DB::transaction(function () use ($amendment, $instance, $approver, $reason) {
            $this->engine->reject($instance, $approver, $reason);
            $amendment->update(['status' => 'rejected']);

            $this->timeline->record($amendment->patient, 'AMENDMENT_REJECTED', "Amendment rejected: {$reason}", $amendment, $approver);

            return $amendment->fresh();
        });
    }

    private function apply(PatientAmendment $amendment, User $actor): void
    {
        $patient = $amendment->patient;
        $patient->update($amendment->proposed_changes);

        $amendment->update(['status' => 'applied', 'applied_at' => now()]);

        $this->timeline->record($patient, 'AMENDMENT_APPLIED', 'Amendment applied to patient record', $amendment, $actor, [
            'changes' => $amendment->proposed_changes,
        ]);
    }
}
