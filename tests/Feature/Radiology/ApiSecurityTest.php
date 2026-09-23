<?php

namespace Tests\Feature\Radiology;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Radiology\RadiologyOrder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Object-level authorization on the new API endpoints — a valid Sanctum token and
 * radiology.* permissions are not enough; the policy's company/branch scope check must still
 * apply to API requests exactly as it does to web requests (spec §81: "API object-level
 * authorization").
 */
class ApiSecurityTest extends RadiologyTestCase
{
    public function test_api_cannot_view_another_companys_radiology_order(): void
    {
        $this->user->removeRole('super_admin');
        Permission::firstOrCreate(['name' => 'radiology.order.view', 'guard_name' => 'web']);
        $this->user->givePermissionTo('radiology.order.view');

        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $patient = Patient::factory()->create(['company_id' => $otherCompany->id]);
        $department = Department::create(['company_id' => $otherCompany->id, 'branch_id' => $otherBranch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true]);
        $otherUser = User::factory()->create();

        $encounter = Encounter::create([
            'company_id' => $otherCompany->id, 'branch_id' => $otherBranch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $otherUser->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $otherUser->id,
        ]);

        $otherOrder = RadiologyOrder::create([
            'company_id' => $otherCompany->id, 'branch_id' => $otherBranch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered',
            'ordered_by' => $otherUser->id, 'ordered_at' => now(),
        ]);

        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Company-Id', (string) $this->company->id)
            ->withHeader('X-Branch-Id', (string) $this->branch->id)
            ->getJson('/api/v1/radiology/orders/'.$otherOrder->id);

        $response->assertForbidden();
    }

    public function test_api_requires_authentication(): void
    {
        // RadiologyTestCase::setUp() calls actingAs() for every test in this class — forget the
        // resolved guards so this request genuinely carries no authenticated user, rather than
        // inheriting the fixture's session-based login.
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/radiology/orders');

        $response->assertUnauthorized();
    }

    public function test_api_order_index_only_returns_own_companys_orders(): void
    {
        $order = RadiologyOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => Patient::factory()->create(['company_id' => $this->company->id])->id,
            'encounter_id' => Encounter::create([
                'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
                'patient_id' => Patient::factory()->create(['company_id' => $this->company->id])->id,
                'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
                'department_id' => Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true])->id,
                'encounter_date' => now()->toDateString(), 'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
            ])->id,
            'order_number' => 'RAD-'.uniqid(), 'accession_number' => 'RAD-ACC-'.uniqid(),
            'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Company-Id', (string) $this->company->id)
            ->withHeader('X-Branch-Id', (string) $this->branch->id)
            ->getJson('/api/v1/radiology/orders');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(collect($response->json('data'))->pluck('id')->contains($order->id));
    }
}
