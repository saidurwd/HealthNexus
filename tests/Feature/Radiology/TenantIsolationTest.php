<?php

namespace Tests\Feature\Radiology;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Radiology\RadiologyOrder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Hospital A's radiology user must not be able to view or act on Hospital B's orders, even with
 * full radiology.* permissions — every policy re-checks organization/branch membership server-side.
 * Spec §57/§81: explicitly the most important security test for this phase.
 */
class TenantIsolationTest extends RadiologyTestCase
{
    private Organization $otherOrganization;

    private Branch $otherBranch;

    private RadiologyOrder $otherOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user->removeRole('super_admin');
        foreach (['radiology.order.view', 'radiology.order.cancel'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->otherOrganization = Organization::factory()->create();
        $this->otherBranch = Branch::factory()->create(['organization_id' => $this->otherOrganization->id]);

        $patient = Patient::factory()->create(['organization_id' => $this->otherOrganization->id]);
        $department = Department::create(['organization_id' => $this->otherOrganization->id, 'branch_id' => $this->otherBranch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true]);
        $otherUser = User::factory()->create();

        $encounter = Encounter::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => $this->otherBranch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $otherUser->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $otherUser->id,
        ]);

        $this->otherOrder = RadiologyOrder::create([
            'organization_id' => $this->otherOrganization->id, 'branch_id' => $this->otherBranch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered',
            'ordered_by' => $otherUser->id, 'ordered_at' => now(),
        ]);
    }

    public function test_cannot_view_another_organizations_radiology_order(): void
    {
        $response = $this->get('/admin/radiology/orders/'.$this->otherOrder->public_id);

        $response->assertForbidden();
    }

    public function test_cannot_cancel_another_organizations_radiology_order(): void
    {
        $response = $this->post('/admin/radiology/orders/'.$this->otherOrder->public_id.'/cancel', ['reason' => 'Testing tenant isolation']);

        $response->assertForbidden();
    }

    public function test_order_index_only_lists_own_organizations_orders(): void
    {
        $response = $this->get('/admin/radiology/orders');

        $response->assertOk();
        $response->assertViewHas('orders', function ($orders) {
            return ! $orders->pluck('id')->contains($this->otherOrder->id);
        });
    }

    public function test_for_tenant_scope_excludes_other_organizations_rows_at_the_query_level(): void
    {
        $ids = RadiologyOrder::query()->forTenant($this->organization->id)->pluck('id');

        $this->assertFalse($ids->contains($this->otherOrder->id));
    }
}
