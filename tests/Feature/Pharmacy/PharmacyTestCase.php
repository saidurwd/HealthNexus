<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Branch;
use App\Models\Company;
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

    protected Company $company;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['access_level' => 'admin']);
        $this->user->branches()->attach($this->branch->id, ['access_level' => 'manager', 'company_id' => $this->company->id]);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        session()->put('tenant_company_id', $this->company->id);
        session()->put('tenant_branch_id', $this->branch->id);
        app(TenantContextResolver::class)->setCompanyId($this->company->id);
        app(TenantContextResolver::class)->setBranchId($this->branch->id);
    }

    protected function makeMedication(string $code = 'NAPA-500', string $name = 'Napa 500mg Tablet', bool $isControlled = false): PharmacyMedication
    {
        $generic = PharmacyGeneric::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'GEN-'.uniqid(), 'generic_name' => 'Paracetamol', 'is_active' => true,
        ]);
        $dosageForm = PharmacyDosageForm::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'TAB-'.uniqid(), 'name' => 'Tablet', 'is_active' => true,
        ]);
        $route = PharmacyRoute::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'ORAL-'.uniqid(), 'name' => 'Oral', 'is_active' => true,
        ]);

        return PharmacyMedication::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'generic_id' => $generic->id, 'dosage_form_id' => $dosageForm->id, 'route_id' => $route->id,
            'code' => $code, 'name' => $name, 'strength' => '500', 'strength_unit' => 'mg',
            'dispensing_unit' => 'tablet', 'is_prescription_required' => true,
            'is_controlled' => $isControlled, 'is_active' => true,
        ]);
    }

    protected function makeStore(string $code = 'PH-MAIN'): PharmacyStore
    {
        return PharmacyStore::create([
            'company_id' => $this->company->id, 'branch_id' => null,
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
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'doctor_id' => $this->user->id,
            'prescription_no' => 'RX-'.uniqid(), 'status' => 'draft',
            'created_by' => $this->user->id, 'prescribed_at' => now(),
        ]);

        foreach ($items as $item) {
            $prescription->items()->create([
                'company_id' => $this->company->id,
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
