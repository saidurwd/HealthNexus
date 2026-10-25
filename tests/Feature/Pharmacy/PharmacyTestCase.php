<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDosageForm;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyRoute;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Pharmacy\PharmacyStockService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class PharmacyTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $organization;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);

        $this->user = User::factory()->create();
        $this->user->organizations()->attach($this->organization->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'organization_id' => $this->organization->id]);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        session()->put('tenant_organization_id', $this->organization->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setOrganizationId($this->organization->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    protected function makeMedication(string $code = 'NAPA-500', string $name = 'Napa 500mg Tablet', bool $isControlled = false): PharmacyMedication
    {
        $generic = PharmacyGeneric::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => 'GEN-'.uniqid(), 'generic_name' => 'Paracetamol', 'is_active' => true,
        ]);
        $dosageForm = PharmacyDosageForm::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => 'TAB-'.uniqid(), 'name' => 'Tablet', 'is_active' => true,
        ]);
        $route = PharmacyRoute::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => 'ORAL-'.uniqid(), 'name' => 'Oral', 'is_active' => true,
        ]);

        return PharmacyMedication::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'generic_id' => $generic->id, 'dosage_form_id' => $dosageForm->id, 'route_id' => $route->id,
            'code' => $code, 'name' => $name, 'strength' => '500', 'strength_unit' => 'mg',
            'dispensing_unit' => 'tablet', 'is_prescription_required' => true,
            'is_controlled' => $isControlled, 'is_active' => true,
        ]);
    }

    protected function makeStore(string $code = 'PH-MAIN'): PharmacyStore
    {
        return PharmacyStore::create([
            'organization_id' => $this->organization->id, 'branch_id' => null,
            'code' => $code, 'name' => 'Main Store', 'store_type' => 'main', 'is_active' => true,
        ]);
    }

    protected function receiveBatch(PharmacyStore $store, PharmacyMedication $medication, int $quantity, ?string $expiryDate = null): PharmacyBatch
    {
        return app(PharmacyStockService::class)->receiveNewBatch($store, $medication, [
            'batch_number' => 'B-'.uniqid(),
            'expiry_date' => $expiryDate ?? now()->addYear()->toDateString(),
            'unit_cost' => '2.00',
            'selling_price' => '5.00',
        ], $quantity, $this->user);
    }

    protected function makePrescription(\App\Models\Patient $patient, array $items): Prescription
    {
        $prescription = Prescription::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'doctor_id' => $this->user->id,
            'prescription_no' => 'RX-'.uniqid(), 'status' => 'draft',
            'created_by' => $this->user->id, 'prescribed_at' => now(),
        ]);

        foreach ($items as $item) {
            $prescription->items()->create([
                'organization_id' => $this->organization->id,
                'medicine_name' => $item['medicine_name'],
                'frequency' => $item['frequency'] ?? 'TDS',
                'duration' => $item['duration'] ?? '5 days',
                'quantity' => $item['quantity'] ?? 10,
                'is_active' => true,
            ]);
        }

        return $prescription->fresh('items');
    }
}
