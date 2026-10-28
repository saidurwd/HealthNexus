<?php

namespace Tests\Feature\Demo;

use App\Models\Billing\BillingItem;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodUnit;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrJob;
use App\Models\Hr\HrShift;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Services\Insurance\InsurancePolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_and_ed_factories_build_consistent_records(): void
    {
        $item = BillingItem::factory()->forEvent('lab_test', 'HGB')->create();
        $this->assertSame($item->organization_id, $item->category->organization_id);
        $this->assertTrue($item->is_clinically_chargeable);

        $bay = EdBay::factory()->create();
        $this->assertSame($bay->zone->organization_id, $bay->organization_id);

        $visit = EdVisit::factory()->triaged(2)->create();
        $this->assertSame($visit->organization_id, $visit->patient->organization_id);
        $this->assertSame($visit->encounter->patient_id, $visit->patient_id);
        $this->assertSame(2, $visit->triage_level);
    }

    public function test_blood_bank_factories_build_consistent_records(): void
    {
        $donor = BloodDonor::factory()->deferred(10)->create();
        $this->assertSame($donor->organization_id, $donor->patient->organization_id);
        $this->assertTrue($donor->isDeferred());

        $unit = BloodUnit::factory()->component('platelets')->create();
        $this->assertSame(5, (int) round($unit->collected_at->diffInDays($unit->expires_at, true)), 'platelets last days, not weeks');
        $this->assertSame(60, $unit->volume_ml);

        $request = BloodRequest::factory()->priority('urgent')->create();
        $this->assertSame($request->organization_id, $request->patient->organization_id);
    }

    public function test_insurance_factories_build_consistent_records(): void
    {
        $plan = InsurancePlan::factory()->create();
        $this->assertSame($plan->organization_id, $plan->payer->organization_id);

        $payer = InsurancePayer::factory()->type('tpa')->create();
        $this->assertSame('tpa', $payer->payer_type);

        $patient = Patient::factory()->create(['organization_id' => $plan->organization_id]);
        $policy = app(InsurancePolicyService::class)->attach($patient, $plan->payer, $plan, ['policy_number' => 'P1', 'member_number' => 'M1', 'effective_from' => now()->subMonth()->toDateString()]);
        $preauth = InsurancePreauth::factory()->forPolicy($policy)->approved('3000.00')->create();
        $this->assertSame('3000.00', $preauth->remaining());
    }

    public function test_inventory_factories_build_consistent_records(): void
    {
        $item = InventoryItem::factory()->create();
        $this->assertSame($item->organization_id, $item->category->organization_id);

        $batch = InventoryBatch::factory()->create(['organization_id' => $item->organization_id, 'item_id' => $item->id]);
        $this->assertSame($item->id, $batch->item_id);
        $this->assertSame('active', $batch->status);

        $store = InventoryStore::factory()->create(['organization_id' => $item->organization_id]);
        $supplier = ProcurementSupplier::factory()->create(['organization_id' => $item->organization_id]);
        $this->assertSame($store->organization_id, $supplier->organization_id);
        $this->assertNotEmpty($store->public_id);
        $this->assertNotEmpty($supplier->public_id);
    }

    public function test_hr_factories_build_consistent_records(): void
    {
        $employee = HrEmployee::factory()->create();
        $job = HrJob::factory()->create(['organization_id' => $employee->organization_id]);
        $shift = HrShift::factory()->create(['organization_id' => $employee->organization_id]);
        $this->assertSame($employee->organization_id, $job->organization_id);
        $this->assertSame($employee->organization_id, $shift->organization_id);
        $this->assertNotEmpty($employee->public_id);
        $this->assertSame('active', $employee->status);
    }

    public function test_portal_account_factory_builds_a_login_that_matches_its_patient(): void
    {
        $patient = Patient::factory()->create(['phone' => '01712345678']);
        $account = PatientPortalAccount::factory()->create(['patient_id' => $patient->id]);

        $this->assertSame($patient->organization_id, $account->organization_id);
        $this->assertSame('8801712345678', $account->phone);
        $this->assertTrue($account->canLogin());
        $this->assertFalse(PatientPortalAccount::factory()->invited()->create()->canLogin());
    }
}
