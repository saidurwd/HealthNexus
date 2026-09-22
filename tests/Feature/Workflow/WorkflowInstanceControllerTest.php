<?php

namespace Tests\Feature\Workflow;

use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStep;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkflowInstanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowEngine $engine;

    private Company $company;

    private User $initiator;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->engine = app(WorkflowEngine::class);
        $this->company = Company::factory()->create();
        $this->initiator = User::factory()->create();
        $this->approver = User::factory()->create();

        Role::firstOrCreate(['name' => 'approver_role', 'guard_name' => 'web']);
        $this->approver->assignRole('approver_role');

        foreach (['workflow.view', 'workflow.act'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    private function makeOneStepInstance(): \App\Models\WorkflowInstance
    {
        $workflow = Workflow::create(['company_id' => $this->company->id, 'code' => 'ctrl_test', 'name' => 'Controller Test']);
        $step = WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 1, 'name' => 'Review', 'is_final' => true]);
        WorkflowApprover::create(['workflow_step_id' => $step->id, 'approver_type' => WorkflowApprover::TYPE_ROLE, 'role_name' => 'approver_role']);

        $subject = Patient::factory()->create(['company_id' => $this->company->id]);
        $instance = $this->engine->start($workflow, $subject, $this->initiator);

        return $this->engine->submit($instance, $this->initiator);
    }

    public function test_approver_sees_instance_in_my_approvals(): void
    {
        $instance = $this->makeOneStepInstance();

        $this->approver->givePermissionTo('workflow.view');
        $this->actingAs($this->approver);

        $response = $this->get('/admin/workflow');

        $response->assertOk();
        $pending = $response->viewData('pending');
        $this->assertTrue($pending->contains('id', $instance->id));
    }

    public function test_approver_can_approve_via_http(): void
    {
        $instance = $this->makeOneStepInstance();

        $this->approver->givePermissionTo(['workflow.view', 'workflow.act']);
        $this->actingAs($this->approver);

        $this->post("/admin/workflow/{$instance->id}/approve")->assertRedirect();

        $instance->refresh();
        $this->assertSame('approved', $instance->status);
    }

    public function test_ineligible_user_gets_a_validation_error_not_a_crash(): void
    {
        $instance = $this->makeOneStepInstance();

        $stranger = User::factory()->create();
        $stranger->givePermissionTo('workflow.act');
        $this->actingAs($stranger);

        $this->post("/admin/workflow/{$instance->id}/approve")
            ->assertSessionHasErrors('approver');

        $instance->refresh();
        $this->assertSame('pending_approval', $instance->status);
    }

    public function test_reject_requires_a_reason(): void
    {
        $instance = $this->makeOneStepInstance();

        $this->approver->givePermissionTo(['workflow.view', 'workflow.act']);
        $this->actingAs($this->approver);

        $this->post("/admin/workflow/{$instance->id}/reject", [])
            ->assertSessionHasErrors('reason');
    }

    public function test_user_without_workflow_act_permission_cannot_approve(): void
    {
        $instance = $this->makeOneStepInstance();

        $this->approver->givePermissionTo('workflow.view');
        $this->actingAs($this->approver);

        $this->post("/admin/workflow/{$instance->id}/approve")->assertForbidden();
    }

    public function test_show_page_displays_the_timeline(): void
    {
        $instance = $this->makeOneStepInstance();

        $this->initiator->givePermissionTo('workflow.view');
        $this->actingAs($this->initiator);

        $response = $this->get("/admin/workflow/{$instance->id}");

        $response->assertOk();
        $response->assertSee('Review');
    }
}
