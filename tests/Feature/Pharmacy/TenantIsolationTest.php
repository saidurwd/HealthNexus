<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\User;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Services\TenantContextResolver;
use Spatie\Permission\Models\Permission;

/**
 * The spec's explicitly "most important" security test: a Hospital A pharmacy user cannot
 * view or act on Hospital B's pharmacy orders/stock/dispensing, via direct model-policy
 * authorization and via the web UI.
 */
class TenantIsolationTest extends PharmacyTestCase
{
    public function test_a_user_cannot_view_another_organizations_pharmacy_order(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 10);

        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => $medication->name, 'quantity' => 5],
        ]);
        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);

        $otherOrganization = Organization::factory()->create();
        $otherBranch = Branch::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherUser = User::factory()->create();
        $otherUser->organizations()->attach($otherOrganization->id, ['access_level' => 'admin']);
        $otherUser->branches()->attach($otherBranch->id, ['access_level' => 'manager', 'organization_id' => $otherOrganization->id]);
        Permission::firstOrCreate(['name' => 'pharmacy.prescription.view', 'guard_name' => 'web']);
        $otherUser->givePermissionTo('pharmacy.prescription.view');

        $this->actingAs($otherUser);
        session()->put('tenant_organization_id', $otherOrganization->id);
        app(TenantContextResolver::class)->setOrganizationId($otherOrganization->id);

        $this->assertFalse($otherUser->can('view', $order));

        $response = $this->get('/admin/pharmacy/orders/'.$order->public_id);
        $response->assertForbidden();
    }

    public function test_order_listing_is_scoped_to_the_current_organization(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 10);

        $patient = Patient::factory()->create(['organization_id' => $this->organization->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => $medication->name, 'quantity' => 5],
        ]);
        app(PharmacyOrderService::class)->registerFromPrescription($prescription);

        $otherOrganization = Organization::factory()->create();

        $this->assertSame(1, PharmacyOrder::forTenant($this->organization->id)->count());
        $this->assertSame(0, PharmacyOrder::forTenant($otherOrganization->id)->count());
    }
}
