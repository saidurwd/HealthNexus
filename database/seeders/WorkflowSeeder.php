<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Workflow;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

/**
 * Seeds one example workflow per company as a reference implementation for future modules
 * (billing discounts, insurance approvals, purchase requisitions, etc.) to model their own
 * workflow definitions on — not meant to be the only workflow the system ever has.
 */
class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            $workflow = Workflow::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => 'generic_approval'],
                ['name' => 'Generic Approval', 'description' => 'Two-step reference approval workflow: supervisor review, then final sign-off.', 'is_active' => true],
            );

            if ($workflow->steps()->exists()) {
                return;
            }

            $review = WorkflowStep::create([
                'workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Supervisor Review', 'is_final' => false,
            ]);
            WorkflowApprover::create([
                'workflow_step_id' => $review->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'hospital_admin',
            ]);

            $final = WorkflowStep::create([
                'workflow_id' => $workflow->id, 'step_order' => 2, 'name' => 'Final Approval', 'is_final' => true,
            ]);
            WorkflowApprover::create([
                'workflow_step_id' => $final->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'super_admin',
            ]);

            $this->seedPatientAmendmentWorkflow($company);
            $this->seedIpdAdmissionWorkflow($company);
        });
    }

    /**
     * Backs Phase 1's patient amendment/correction flow (App\Services\Patients\
     * PatientAmendmentService): a single hospital_admin approval step for sensitive-field
     * corrections (name, DOB, sex, national identifier).
     */
    private function seedPatientAmendmentWorkflow(Company $company): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'patient_amendment'],
            ['name' => 'Patient Record Amendment', 'description' => 'Approval required to correct sensitive patient identity fields.', 'is_active' => true],
        );

        if ($workflow->steps()->exists()) {
            return;
        }

        $approval = WorkflowStep::create([
            'workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Admin Approval', 'is_final' => true,
        ]);
        WorkflowApprover::create([
            'workflow_step_id' => $approval->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'hospital_admin',
        ]);
    }

    /**
     * Backs Phase 8's IPD admission request approval (App\Services\Ipd\IpdAdmissionRequestService)
     * — a single approval step, eligible to either an ipd_coordinator or a hospital_admin,
     * honoring the spec's explicit instruction to reuse the generic engine rather than build a
     * second approval framework.
     */
    private function seedIpdAdmissionWorkflow(Company $company): void
    {
        $workflow = Workflow::query()->firstOrCreate(
            ['company_id' => $company->id, 'code' => 'ipd_admission'],
            ['name' => 'IPD Admission Approval', 'description' => 'Approval required before an admission request becomes an active admission.', 'is_active' => true],
        );

        if ($workflow->steps()->exists()) {
            return;
        }

        $approval = WorkflowStep::create([
            'workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Admission Approval', 'is_final' => true,
        ]);
        WorkflowApprover::create([
            'workflow_step_id' => $approval->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'ipd_coordinator',
        ]);
        WorkflowApprover::create([
            'workflow_step_id' => $approval->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'hospital_admin',
        ]);
    }
}
