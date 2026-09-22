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
        });
    }
}
