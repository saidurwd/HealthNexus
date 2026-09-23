<?php

namespace Tests\Feature\Laboratory;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Patient;
use App\Models\User;
use App\Services\TenantContextResolver;
use Spatie\Permission\Models\Permission;

/**
 * Hospital A's lab user must not be able to view or act on Hospital B's lab orders/specimens,
 * even with full lab.* permissions — every policy re-checks company/branch membership
 * server-side. Per spec §55/§51: "Hospital A laboratory user cannot access Hospital B
 * laboratory data" is explicitly the most important security test for this phase.
 */
class TenantIsolationTest extends LabTestCase
{
    private Company $otherCompany;

    private Branch $otherBranch;

    private LabOrder $otherOrder;

    private LabSpecimen $otherSpecimen;

    protected function setUp(): void
    {
        parent::setUp();

        // Gate::before grants super_admin every ability unconditionally, which would bypass the
        // very tenant-scope checks this test exists to verify.
        $this->user->removeRole('super_admin');
        foreach (['lab.order.view', 'lab.order.cancel', 'lab.specimen.view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $this->otherCompany = Company::factory()->create();
        $this->otherBranch = Branch::factory()->create(['company_id' => $this->otherCompany->id]);

        $patient = Patient::factory()->create(['company_id' => $this->otherCompany->id]);
        $department = Department::create(['company_id' => $this->otherCompany->id, 'branch_id' => $this->otherBranch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);
        $otherUser = User::factory()->create();

        $encounter = Encounter::create([
            'company_id' => $this->otherCompany->id, 'branch_id' => $this->otherBranch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $otherUser->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $otherUser->id,
        ]);

        $this->otherOrder = LabOrder::create([
            'company_id' => $this->otherCompany->id, 'branch_id' => $this->otherBranch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $otherUser->id, 'ordered_at' => now(),
        ]);

        $specimenType = LabSpecimenType::create(['company_id' => $this->otherCompany->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);

        $this->otherSpecimen = LabSpecimen::create([
            'company_id' => $this->otherCompany->id, 'branch_id' => $this->otherBranch->id, 'lab_order_id' => $this->otherOrder->id,
            'specimen_type_id' => $specimenType->id, 'accession_number' => 'ACC-'.uniqid(), 'status' => 'collected',
            'collected_by' => $otherUser->id, 'collected_at' => now(),
        ]);
    }

    public function test_cannot_view_another_companys_lab_order(): void
    {
        $response = $this->get('/admin/lab/orders/'.$this->otherOrder->id);

        $response->assertForbidden();
    }

    public function test_cannot_view_another_companys_specimen(): void
    {
        $response = $this->get('/admin/lab/specimens/'.$this->otherSpecimen->id);

        $response->assertForbidden();
    }

    public function test_cannot_cancel_another_companys_lab_order(): void
    {
        $response = $this->post('/admin/lab/orders/'.$this->otherOrder->id.'/cancel', ['reason' => 'Testing tenant isolation']);

        $response->assertForbidden();
    }

    public function test_order_index_only_lists_own_companys_orders(): void
    {
        $response = $this->get('/admin/lab/orders');

        $response->assertOk();
        $response->assertViewHas('orders', function ($orders) {
            return ! $orders->pluck('id')->contains($this->otherOrder->id);
        });
    }

    public function test_forTenant_scope_excludes_other_companys_rows_at_the_query_level(): void
    {
        $ids = LabOrder::query()->forTenant($this->company->id)->pluck('id');

        $this->assertFalse($ids->contains($this->otherOrder->id));
    }
}
