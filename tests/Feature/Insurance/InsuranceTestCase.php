<?php

namespace Tests\Feature\Insurance;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingInvoiceItem;
use App\Models\Billing\BillingItem;
use App\Models\Branch;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\Insurance\InsurancePolicyService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class InsuranceTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->staff(['super_admin']);
        $this->actingAs($this->user);
        $this->context();
    }

    protected function context(?Organization $organization = null, ?Branch $branch = null): void
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    protected function staff(array $roles = [], ?Organization $organization = null, ?Branch $branch = null): User
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    protected function payer(array $o = []): InsurancePayer
    {
        return InsurancePayer::factory()->create($o + ['organization_id' => $this->organization->id]);
    }

    protected function plan(?InsurancePayer $payer = null, array $o = []): InsurancePlan
    {
        $payer ??= $this->payer();

        return InsurancePlan::factory()->create($o + ['organization_id' => $payer->organization_id, 'payer_id' => $payer->id, 'copay_percent' => 20, 'deductible_amount' => 0, 'annual_limit' => 500000]);
    }

    protected function policy(Patient $patient, ?InsurancePlan $plan = null, array $o = []): BillingInsurancePolicy
    {
        $plan ??= $this->plan();

        return app(InsurancePolicyService::class)->attach($patient, InsurancePayer::find($plan->payer_id), $plan, $o + [
            'policy_number' => 'POL-'.fake()->unique()->numerify('######'), 'member_number' => 'MEM-'.fake()->unique()->numerify('######'), 'effective_from' => now()->subYear()->toDateString(), 'effective_to' => now()->addYear()->toDateString(),
        ]);
    }

    protected function patient(array $o = []): Patient
    {
        return Patient::factory()->create($o + ['organization_id' => $this->organization->id, 'middle_name' => null]);
    }

    /** A finalised invoice for the patient with the given [code, category, gross] lines and (optionally) a coded diagnosis. */
    protected function invoice(Patient $patient, array $lines = [['OPD', 'CONSULT', '1000.00'], ['CBC', 'LAB', '2000.00']], ?BillingInsurancePolicy $policy = null, bool $diagnosis = true, string $type = 'opd'): BillingInvoice
    {
        $encounter = Encounter::factory()->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id]);
        $diagnosis && Diagnosis::factory()->create(['organization_id' => $this->organization->id, 'encounter_id' => $encounter->id, 'patient_id' => $patient->id, 'code' => 'J06.9', 'coding_system' => 'ICD-10', 'description' => 'Acute upper respiratory infection']);
        $total = array_reduce($lines, fn ($c, $l) => bcadd($c, $l[2], 2), '0.00');
        $invoice = BillingInvoice::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'insurance_policy_id' => $policy?->id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'), 'invoice_type' => $type, 'status' => 'finalized', 'currency' => 'BDT', 'subtotal' => $total, 'discount_type' => 'none', 'discount_amount' => '0.00',
            'tax_amount' => '0.00', 'rounding_amount' => '0.00', 'grand_total' => $total, 'paid_amount' => '0.00', 'due_amount' => $total, 'invoice_date' => now()->toDateString(), 'billing_party_type' => 'patient', 'billing_party_id' => $patient->id, 'version' => 1, 'created_by' => $this->user->id,
        ]);
        foreach ($lines as [$code, $category, $gross]) {
            $cat = BillingCategory::firstOrCreate(['organization_id' => $this->organization->id, 'branch_id' => null, 'code' => $category], ['name' => $category, 'is_active' => true]);
            $item = BillingItem::firstOrCreate(['organization_id' => $this->organization->id, 'branch_id' => null, 'item_code' => $code], ['category_id' => $cat->id, 'item_type' => 'service', 'name' => $code, 'unit' => 'each', 'base_price' => $gross, 'is_taxable' => false, 'is_active' => true]);
            BillingInvoiceItem::create(['invoice_id' => $invoice->id, 'billing_item_id' => $item->id, 'description' => $code, 'quantity' => 1, 'unit_price' => $gross, 'gross_amount' => $gross, 'discount_type' => 'none', 'discount_amount' => '0.00', 'tax_rate' => '0.0000', 'tax_amount' => '0.00', 'net_amount' => $gross]);
        }

        return $invoice->refresh();
    }
}
