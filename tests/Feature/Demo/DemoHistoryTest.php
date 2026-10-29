<?php

namespace Tests\Feature\Demo;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\Analytics\Warehouse;
use App\Services\Finance\LedgerService;
use App\Services\Finance\ReconciliationService;
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
use Illuminate\Support\Facades\Schema;
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
        $has('hr_employees', 30);
        $has('hr_roster_entries', 300);
        $has('hr_punches', 300);
        $has('hr_attendance_days', 300);
        $has('hr_leave_ledger', 30);
        $has('patient_portal_accounts', 8, ['status' => 'active']);
        $has('portal_result_releases', 3);
        $has('portal_threads', 5);
        $has('portal_release_policies', 2);
        $has('crm_interactions', 5);
        $has('crm_segments', 3, ['status' => 'active']);
        $has('crm_campaigns', 2, ['status' => 'completed']);
        $has('crm_complaints', 1);
        $has('crm_form_submissions', 2);
        $has('integration_partners', 5);
        $has('integration_routes', 4);
        $has('integration_messages', 10);
        $this->assertGreaterThanOrEqual(10, DB::table('integration_attempts as a')->join('integration_messages as m', 'm.id', '=', 'a.message_id')->where('m.organization_id', $org)->count(), 'attempts are recorded');
        $has('integration_access_log', 1);

        $has('fin_accounts', 40);
        $has('fin_journals', 50, ['status' => 'posted']);
        $has('fin_receivables', 20);
        $has('fin_bank_accounts', 3);
        $has('fin_assets', 5);
        $has('fin_postings', 20, ['status' => 'posted']);

        $has('analytics_reports', 3);
        $has('analytics_schedules', 2);
        $has('analytics_datasets', 1);
        $has('analytics_forecasts', 5);

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

        // the portal: a result reaches a patient only after a decision, a critical one only after its alert was acknowledged, marketing only to those who said yes, online money is in Billing exactly once
        $this->assertSame(0, DB::table('portal_result_releases')->where('organization_id', $org)->where('status', 'released')->whereNull('released_at')->count());
        $this->assertSame(0, DB::table('portal_result_releases as r')->where('r.organization_id', $org)->where('r.status', 'released')->where('r.critical', true)->where('r.source_type', 'lab_report')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('lab_critical_result_alerts as a')->join('lab_results as lr', 'lr.id', '=', 'a.result_id')->join('lab_order_items as i', 'i.id', '=', 'lr.lab_order_item_id')->join('lab_reports as lrep', 'lrep.lab_order_id', '=', 'i.lab_order_id')->whereColumn('lrep.id', 'r.source_id')->whereNotNull('a.acknowledged_at'))->count(), 'no critical result was released without an acknowledged alert');
        $this->assertSame(0, DB::table('portal_outbox as o')->where('o.organization_id', $org)->where('o.purpose', 'marketing')->whereIn('o.status', ['sent', 'queued', 'deferred'])->whereNotExists(fn ($q) => $q->selectRaw('1')->from('portal_comm_preferences as p')->whereColumn('p.patient_id', 'o.patient_id')->whereColumn('p.channel', 'o.channel')->where('p.purpose', 'marketing')->where('p.opted_in', true))->count(), 'marketing went only to patients who opted in');
        $this->assertSame(0, DB::table('portal_payments')->where('organization_id', $org)->where('status', 'succeeded')->whereNotNull('billing_payment_id')->groupBy('billing_payment_id')->havingRaw('count(*) > 1')->get()->count(), 'no online payment was posted to Billing twice');
        $this->assertSame(0, DB::table('portal_payments as p')->where('p.organization_id', $org)->where('p.status', 'succeeded')->whereNotNull('p.billing_payment_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('billing_payments as b')->whereColumn('b.id', 'p.billing_payment_id')->whereColumn('b.amount', 'p.amount'))->count());
        $this->assertSame(0, DB::table('crm_campaigns')->where('organization_id', $org)->whereIn('status', ['approved', 'running', 'completed'])->whereColumn('approved_by', 'created_by')->count(), 'nobody approved their own campaign');
        $this->assertSame(0, DB::table('portal_proxies')->where('organization_id', $org)->where('status', 'active')->whereNull('valid_to')->count());
        $this->assertSame(0, DB::table('patient_portal_accounts')->where('organization_id', $org)->where('status', 'active')->whereNull('password')->count());

        // interoperability: nothing went to a partner that needs consent without it, one idempotency key once, a dead message always says why, nothing is stuck sending
        $this->assertSame(0, DB::table('integration_messages as m')->join('integration_partners as p', 'p.id', '=', 'm.partner_id')->where('m.organization_id', $org)->where('m.direction', 'out')->where('p.requires_consent', true)->whereIn('m.status', ['processed', 'queued', 'retrying'])->whereNotNull('m.patient_id')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('patient_consents as c')->whereColumn('c.patient_id', 'm.patient_id')->where('c.status', 'active'))->count(), 'nothing was sent about a patient who did not agree');
        $this->assertSame(0, DB::table('integration_messages')->where('organization_id', $org)->groupBy('direction', 'idempotency_key')->havingRaw('count(*) > 1')->get()->count());
        $this->assertSame(0, DB::table('integration_messages')->where('organization_id', $org)->where('status', 'dead')->whereNull('error')->count());
        $this->assertSame(0, DB::table('integration_messages')->where('organization_id', $org)->where('status', 'sending')->count(), 'nothing is left stuck sending');

        // finance: the books balance, every posted journal balances on its own, Billing agrees with the books, what customers owe agrees with the control accounts, nothing was posted into a closed period
        $this->assertSame(0, DB::table('fin_journals')->where('organization_id', $org)->where('status', 'posted')->whereRaw('ABS(total_debit - total_credit) > 0.001')->count(), 'every posted journal balances');
        $this->assertSame(0, DB::table('fin_journals as j')->where('j.organization_id', $org)->where('j.status', 'posted')->whereRaw('ABS(j.total_debit - (select coalesce(sum(l.debit), 0) from fin_journal_lines l where l.journal_id = j.id)) > 0.001')->count(), 'journal totals equal their lines');
        $tb = app(LedgerService::class)->trialBalance($org, null, now()->toDateString());
        $this->assertTrue(app(LedgerService::class)->totals($tb)['balanced'], 'the trial balance balances');
        $recon = app(ReconciliationService::class)->billingVsLedger($org, '2000-01-01', now()->toDateString());
        $this->assertSame([], collect($recon['rows'])->filter(fn ($r) => bccomp($r['difference'], '0', 2) !== 0)->pluck('label')->all(), 'Billing agrees with the books: '.json_encode(app(ReconciliationService::class)->receivableDifferences($org, now()->toDateString(), 10)));
        $this->assertSame(0, DB::table('fin_postings')->where('organization_id', $org)->where('status', 'failed')->count(), 'no posting is left failed');
        $this->assertSame(0, DB::table('fin_payables')->where('organization_id', $org)->whereRaw('settled > amount + 0.001')->count());
        $this->assertSame(0, DB::table('fin_journals as j')->join('fin_periods as p', 'p.id', '=', 'j.period_id')->where('j.organization_id', $org)->where('j.status', 'posted')->where('p.status', 'closed')->where('j.kind', '<>', 'closing')->where('j.posted_at', '>', DB::raw('p.locked_at'))->count());
        $this->assertSame(0, DB::table('fin_journals')->where('organization_id', $org)->where('status', 'posted')->whereNull('period_id')->count(), 'every posted journal sits in a period');
        $this->assertSame(0, DB::table('fin_supplier_payments')->where('organization_id', $org)->where('status', 'paid')->whereColumn('approved_by', 'created_by')->count(), 'nobody approved their own supplier payment');
        $this->assertSame(0, DB::table('fin_journals')->where('organization_id', $org)->where('status', 'posted')->where('kind', 'manual')->whereColumn('approved_by', 'created_by')->count(), 'nobody approved their own journal');
        $this->assertSame(0, DB::table('fin_journals as j')->where('j.organization_id', $org)->whereNotNull('j.reversed_by_id')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('fin_journals as r')->whereColumn('r.id', 'j.reversed_by_id')->whereColumn('r.reversal_of_id', 'j.id'))->count(), 'every reversal points back');

        // analytics: the warehouse is loaded and agrees with the records, holds nothing that identifies a patient, no serious data-quality issue is open, the standard content is in place
        $wh = app(Warehouse::class);
        $this->assertGreaterThan(15, $wh->table('fact_encounters')->where('organization_id', $org)->count(), 'visits reached the warehouse');
        $this->assertSame(Encounter::where('organization_id', $org)->count(), $wh->table('fact_encounters')->where('organization_id', $org)->count(), 'one fact per visit');
        $this->assertSame(bcadd((string) DB::table('billing_invoices')->where('organization_id', $org)->whereNotNull('finalized_at')->whereNotIn('status', ['draft', 'cancelled'])->sum('grand_total'), '0', 2), bcadd((string) $wh->table('fact_invoices')->where('organization_id', $org)->where('is_billed', true)->sum('grand_total'), '0', 2), 'billed money agrees with Billing');
        foreach (['dim_patient', 'fact_encounters', 'fact_appointments', 'fact_lab_orders', 'fact_admissions', 'fact_invoices'] as $table) {
            $columns = array_map('strtolower', Schema::connection(config('analytics.connection') ?: null)->getColumnListing($table));
            $this->assertSame([], array_values(array_intersect($columns, ['patient_id', 'first_name', 'last_name', 'phone', 'email', 'national_identifier', 'address', 'date_of_birth', 'name'])), "$table holds nothing that names a patient");
        }
        $this->assertSame(0, DB::table('analytics_dq_issues')->where('status', 'open')->where('severity', 'error')->where(fn ($q) => $q->where('organization_id', $org)->orWhereNull('organization_id'))->count(), 'no serious data-quality issue is open: '.json_encode(DB::table('analytics_dq_issues')->where('status', 'open')->where('severity', 'error')->get(['rule_code', 'detail'])).' '.json_encode(DB::table('analytics_loads')->where('status', 'failed')->get(['source_key', 'error'])).' '.json_encode(DB::table('analytics_sources')->get(['key', 'last_status', 'last_run_at'])));
        $this->assertSame(25, DB::table('analytics_kpis')->whereNull('organization_id')->count());
        $this->assertSame(5, DB::table('analytics_dashboards')->whereNull('organization_id')->count());
        $this->assertSame(0, DB::table('analytics_loads')->where('status', 'failed')->count(), 'every load succeeded');
        $this->assertGreaterThanOrEqual(1, DB::table('analytics_deliveries')->where('status', 'sent')->count(), 'a scheduled report was sent');
        $this->assertSame(0, DB::table('analytics_datasets')->where('organization_id', $org)->whereColumn('decided_by', 'requested_by')->count(), 'nobody approved their own dataset');

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
