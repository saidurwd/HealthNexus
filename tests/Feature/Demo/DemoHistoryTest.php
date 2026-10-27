<?php

namespace Tests\Feature\Demo;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Inventory\InventoryLedgerReconciler;
use Carbon\Carbon;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ClinicalMasterDataSeeder;
use Database\Seeders\IcuSeeder;
use Database\Seeders\IpdSeeder;
use Database\Seeders\LabSeeder;
use Database\Seeders\NursingSeeder;
use Database\Seeders\OtSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PharmacySeeder;
use Database\Seeders\RadiologySeeder;
use Database\Seeders\SettingsTableSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Runs the whole demo history (AI-RULES 16) for one month at a small volume on a freshly seeded organization, and checks each module
 * has data spread over the period. Every phase adds its module's assertions here.
 */
class DemoHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create(['code' => 'DEMOH']);
        Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->seed([PermissionSeeder::class, SettingsTableSeeder::class, BillingSeeder::class, LabSeeder::class, RadiologySeeder::class, PharmacySeeder::class, IpdSeeder::class, NursingSeeder::class, OtSeeder::class, IcuSeeder::class, WorkflowSeeder::class, ClinicalMasterDataSeeder::class]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function run_(): string
    {
        $this->assertSame(0, Artisan::call('demo:history', ['organization' => 'DEMOH', '--months' => 1, '--visits-per-day' => 2, '--password' => 'demo-pass-123']));

        return Artisan::output();
    }

    public function test_history_is_created_across_the_period_with_test_users(): void
    {
        $output = $this->run_();
        $org = $this->organization->id;

        $this->assertStringContainsString('superuser@demoh.example.invalid', $output);
        $this->assertGreaterThan(20, Patient::where('organization_id', $org)->count());
        $this->assertGreaterThan(20, Appointment::where('organization_id', $org)->count());
        $this->assertGreaterThan(15, Encounter::where('organization_id', $org)->whereIn('status', ['locked', 'completed'])->count());

        $has = fn (string $table, int $min, array $where = []) => $this->assertGreaterThanOrEqual($min, DB::table($table)->where('organization_id', $org)->where($where)->count(), "$table has history");

        // every built module has data
        $has('lab_orders', 10, ['status' => 'reported']);
        $has('lab_results', 10);
        $has('radiology_orders', 1);
        $has('pharmacy_dispensings', 5);
        $has('pharmacy_batches', 10);
        $has('ipd_admissions', 10);
        $has('ipd_bed_allocations', 10);
        $has('nursing_episodes', 10);
        $has('nursing_medication_administrations', 20);
        $has('ot_surgeries', 3);
        $has('icu_episodes', 3);
        $has('icu_observations', 20);
        $has('ed_visits', 15);
        $has('ed_dispositions', 10);
        $has('blood_donors', 5);
        $has('blood_donations', 10, ['status' => 'collected']);
        $has('blood_units', 30);
        $has('blood_requests', 5);
        $has('blood_issues', 2);
        $has('blood_transfusions', 2);
        $has('insurance_payers', 5);
        $has('insurance_plans', 5);
        $has('insurance_claims', 5);
        $has('insurance_submissions', 1);
        $has('inventory_items', 20);
        $has('procurement_suppliers', 5);
        $has('procurement_purchase_orders', 5);
        $has('procurement_goods_receipts', 5, ['status' => 'posted']);
        $has('inventory_issues', 3);
        $has('inventory_transfers', 2, ['status' => 'received']);
        $has('inventory_consumptions', 3);
        $has('billing_invoices', 30);
        $has('billing_payments', 20);
        $has('billing_cashier_sessions', 10);
        $has('billing_charges', 50);

        // every bag is accounted for: no bag is issued twice at once, and none transfused twice
        $this->assertSame(0, DB::table('blood_issues')->whereIn('status', ['issued', 'transfusing'])->groupBy('unit_id')->havingRaw('count(*) > 1')->get()->count());
        $this->assertSame(0, DB::table('blood_units')->where('status', 'available')->where('expires_at', '<=', now())->count(), 'no expired bag is left on the shelf');
        $this->assertSame(0, DB::table('blood_issues as i')->join('blood_units as u', 'u.id', '=', 'i.unit_id')->where('i.crossmatched', true)->whereNull('u.abo')->count());

        // claims: the shares add up to the contract amount on every claim, and no payer was paid beyond what it owed
        $this->assertSame(0, DB::table('insurance_claims as c')->where('c.organization_id', $org)->whereRaw('ABS(c.payer_share + c.patient_share - (select coalesce(sum(l.contract_amount), 0) from insurance_claim_lines l where l.claim_id = c.id)) > 0.01')->count());
        $this->assertSame(0, DB::table('insurance_claims')->where('organization_id', $org)->whereRaw('paid_amount + adjusted_amount > payer_share + 0.01')->count());
        $this->assertSame(0, DB::table('insurance_remittances')->where('organization_id', $org)->whereRaw('allocated_amount > amount + 0.01')->count());

        // inventory: the stock projection equals the ledger, nothing is negative, no expired batch is issuable, every order has an approval chain
        $this->assertSame(0, app(InventoryLedgerReconciler::class)->drift($org)->count(), 'inventory stock equals the ledger');
        $this->assertSame(0, DB::table('inventory_stock')->where('organization_id', $org)->where('quantity_on_hand', '<', 0)->count());
        $this->assertSame(0, DB::table('procurement_purchase_orders as o')->where('o.organization_id', $org)->whereIn('o.status', ['approved', 'issued', 'partially_received', 'received', 'closed'])->where('o.po_type', 'standard')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('procurement_approvals as a')->whereColumn('a.document_id', 'o.id')->where('a.document_type', 'purchase_order')->where('a.status', 'approved'))->count(), 'every standard order was approved');
        $this->assertSame(0, DB::table('procurement_approvals as a')->join('procurement_purchase_orders as o', function ($j) {
            $j->on('o.id', '=', 'a.document_id')->where('a.document_type', 'purchase_order');
        })->where('a.status', 'approved')->whereColumn('a.decided_by', 'o.created_by')->count(), 'nobody approved their own order');
        $this->assertSame(0, DB::table('procurement_supplier_invoices')->where('organization_id', $org)->where('status', 'mismatch')->whereNotNull('finance_reference')->count());

        // the money adds up, no bed holds two patients, no ED visit is closed twice
        $this->assertSame(0, DB::table('billing_invoices')->where('organization_id', $org)->whereRaw('ABS(paid_amount + due_amount - grand_total) > 0.01')->count());
        $this->assertSame(0, DB::table('ipd_bed_allocations')->whereNull('released_at')->groupBy('bed_id')->havingRaw('count(*) > 1')->get()->count());
        $this->assertSame(0, DB::table('ed_dispositions')->groupBy('visit_id')->havingRaw('count(*) > 1')->get()->count());

        // running it again is refused instead of duplicating the history
        $appointments = Appointment::count();
        $this->assertSame(1, Artisan::call('demo:history', ['organization' => 'DEMOH', '--months' => 1, '--visits-per-day' => 2]));
        $this->assertStringContainsString('already has demo history', Artisan::output());
        $this->assertSame($appointments, Appointment::count());

        $dates = Encounter::where('organization_id', $org)->pluck('encounter_date')->map(fn ($d) => Carbon::parse($d));
        $this->assertGreaterThan(20, $dates->min()->diffInDays($dates->max()), 'encounters are spread over the month');
        $this->assertTrue($dates->max()->lt(now()->startOfDay()), 'history ends before today');
    }

    public function test_production_is_refused(): void
    {
        $this->app['env'] = 'production';
        $this->assertSame(1, Artisan::call('demo:history', ['organization' => 'DEMOH']));
    }
}
