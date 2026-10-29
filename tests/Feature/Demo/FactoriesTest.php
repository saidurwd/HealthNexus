<?php

namespace Tests\Feature\Demo;

use App\Models\Analytics\AnalyticsDashboard;
use App\Models\Analytics\AnalyticsDataset;
use App\Models\Analytics\AnalyticsKpi;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Billing\BillingItem;
use App\Models\Blood\BloodDonor;
use App\Models\Blood\BloodRequest;
use App\Models\Blood\BloodUnit;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use App\Models\Finance\Account;
use App\Models\Finance\Asset;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CostCenter;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrJob;
use App\Models\Hr\HrShift;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePreauth;
use App\Models\Integration\IntegrationPartner;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Services\Analytics\Semantic\SemanticQuery;
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

    public function test_integration_partner_factory_builds_a_partner_that_needs_consent_unless_told_otherwise(): void
    {
        $partner = IntegrationPartner::factory()->create();
        $this->assertTrue($partner->requires_consent);
        $this->assertSame('active', $partner->status);
        $scanner = IntegrationPartner::factory()->kind('dicom_node')->noConsentNeeded()->create();
        $this->assertFalse($scanner->requires_consent);
        $this->assertNotSame($partner->code, $scanner->code);
    }

    public function test_finance_factories_build_a_chart_a_bank_account_and_an_asset(): void
    {
        $expense = Account::factory()->create();
        $this->assertSame('debit', $expense->normal_side);
        $this->assertSame('credit', Account::factory()->type('revenue')->create()->normal_side);
        $this->assertFalse(Account::factory()->heading()->create()->postable);
        $bank = BankAccount::factory()->create();
        $this->assertSame('asset', $bank->account_id ? Account::find($bank->account_id)->type : null);
        $asset = Asset::factory()->create();
        $this->assertSame('120000.00', (string) $asset->cost);
        $this->assertNotSame(CostCenter::factory()->create()->code, CostCenter::factory()->create()->code);
    }

    public function test_analytics_factories_build_content_that_the_semantic_model_accepts(): void
    {
        $kpi = AnalyticsKpi::factory()->create();
        $report = AnalyticsReport::factory()->create();
        $dash = AnalyticsDashboard::factory()->create();
        $ds = AnalyticsDataset::factory()->create();
        $queries = app(SemanticQuery::class);
        $this->assertSame('encounters', $queries->normalise(['fact' => $kpi->definition['fact'], 'measures' => [$kpi->definition['measure']]])['fact']);
        $this->assertSame('revenue', $queries->normalise($report->query)['fact']);
        $this->assertNotEmpty($dash->widgets);
        $this->assertSame('requested', $ds->status);
    }
}
