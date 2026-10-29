<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Period;
use App\Models\User;
use App\Services\Finance\AssetService;
use App\Services\Finance\BudgetService;
use App\Services\Finance\FeeShareService;
use App\Services\Finance\FiscalService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\StatementService;
use App\Services\Finance\YearEndService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StatementsTest extends FinanceTestCase
{
    private function bal(string $code): string
    {
        return app(LedgerService::class)->balance($this->organization->id, Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->first());
    }

    private function id(string $code): int
    {
        return (int) Account::query()->where(['organization_id' => $this->organization->id, 'code' => $code])->value('id');
    }

    /** A small year: capital in, equipment bought, sales, costs, a supplier bill, some cash. */
    private function trade(): void
    {
        $this->postJournal('1120', '3100', '100000.00', '2026-01-02', 'Owner capital');
        $this->postJournal('1510', '1120', '60000.00', '2026-01-05', 'Equipment');
        $this->postJournal('1110', '4100', '30000.00', '2026-02-10', 'OPD cash');
        $this->postJournal('1400', '4300', '20000.00', '2026-03-10', 'Lab on credit');
        $this->postJournal('5100', '1120', '12000.00', '2026-03-31', 'Salaries');
        $this->postJournal('5800', '2340', '5000.00', '2026-04-15', 'Rent owed');
        $this->postJournal('1110', '1400', '8000.00', '2026-05-02', 'Collected');
    }

    public function test_income_statement_balance_sheet_and_cash_flow_agree(): void
    {
        $this->trade();
        $s = app(StatementService::class);
        $is = $s->incomeStatement($this->organization->id, '2026-01-01', '2026-12-31');
        $this->assertSame('50000.00', $is['total_revenue']);
        $this->assertSame('17000.00', $is['total_expense']);
        $this->assertSame('33000.00', $is['net']);
        $bs = $s->balanceSheet($this->organization->id, '2026-12-31');
        $this->assertSame('33000.00', $bs['current_result']);
        $this->assertTrue($bs['balanced'], json_encode([$bs['total_assets'], $bs['total_liabilities'], $bs['total_equity'], $bs['current_result']]));
        $this->assertSame('5000.00', $bs['total_liabilities']);
        $cf = $s->cashFlow($this->organization->id, '2026-01-01', '2026-12-31');
        $this->assertSame('33000.00', $cf['result']);
        $this->assertSame('-60000.00', $cf['investing']);
        $this->assertSame('100000.00', $cf['financing']);
        $this->assertSame('0.00', $cf['opening_cash']);
        $this->assertSame('66000.00', $cf['closing_cash']);
        $this->assertTrue($cf['reconciles'], json_encode($cf));
        $this->assertSame($cf['net_change'], $cf['closing_cash']);
    }

    public function test_profitability_by_cost_centre_and_the_income_statement_filter(): void
    {
        $org = $this->organization->id;
        $this->chart()->createCostCenter($org, 'OPD', 'Outpatient', 'profit');
        $this->chart()->createCostCenter($org, 'LAB', 'Laboratory', 'profit');
        $j = fn (string $cc, string $rev, string $amount) => $this->journals()->postAuto($org, ['date' => '2026-09-05', 'memo' => $cc, 'source_type' => 't', 'source_id' => $cc.$amount, 'event_key' => 'x'], [
            ['account' => '1110', 'debit' => $amount, 'credit' => '0'], ['account' => $rev, 'debit' => '0', 'credit' => $amount, 'cost_center' => $cc]]);
        $j('OPD', '4100', '1000');
        $j('LAB', '4300', '700');
        $this->journals()->postAuto($org, ['date' => '2026-09-06', 'memo' => 'lab reagents', 'source_type' => 't', 'source_id' => 'r', 'event_key' => 'x'], [['account' => '5200', 'debit' => '200', 'credit' => '0', 'cost_center' => 'LAB'], ['account' => '1110', 'debit' => '0', 'credit' => '200']]);
        $rows = app(StatementService::class)->profitability($org, '2026-09-01', '2026-09-30');
        $lab = $rows->firstWhere('code', 'LAB');
        $this->assertSame(['700.00', '200.00', '500.00'], [$lab['revenue'], $lab['expense'], $lab['result']]);
        $this->assertSame('1000.00', $rows->firstWhere('code', 'OPD')['result']);
        $only = app(StatementService::class)->incomeStatement($org, '2026-09-01', '2026-09-30', CostCenter::query()->where('code', 'LAB')->value('id'));
        $this->assertSame('500.00', $only['net']);
    }

    public function test_closing_the_year_sweeps_the_result_to_retained_earnings_and_locks_everything(): void
    {
        $this->trade();
        $year = $this->year;
        $by = $this->accountant();
        $this->travelTo('2027-01-15');
        $this->assertSame([], app(YearEndService::class)->blockers($year));
        $closed = app(YearEndService::class)->close($year, $by);
        $this->assertSame('closed', $closed->status);
        $this->assertSame('33000.00', $this->bal('3200'), 'retained earnings');
        $this->assertSame('0.00', $this->bal('4100'));
        $this->assertSame('0.00', $this->bal('5100'));
        $bs = app(StatementService::class)->balanceSheet($this->organization->id, '2026-12-31');
        $this->assertSame('0.00', $bs['current_result']);
        $this->assertTrue($bs['balanced']);
        $this->assertSame('50000.00', app(StatementService::class)->incomeStatement($this->organization->id, '2026-01-01', '2026-12-31')['total_revenue'], 'the year\'s statement still shows the trading, not the closing entries');
        $this->assertSame(0, Period::query()->where('fiscal_year_id', $year->id)->where('status', '<>', 'closed')->count());
        try {
            $this->postJournal('1110', '4100', '5.00', '2026-12-15');
            $this->fail('posted into a closed year');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('closed', $e->getMessage().json_encode(method_exists($e, 'errors') ? $e->errors() : []));
        }
        $this->expectException(ValidationException::class);
        app(YearEndService::class)->close($year, $by);
    }

    public function test_a_year_with_a_pending_journal_cannot_close(): void
    {
        $maker = $this->accountant();
        $this->journals()->submit($this->journals()->createDraft($this->organization->id, ['date' => '2026-11-05', 'memo' => 'x'], [['account' => '1120', 'debit' => '10', 'credit' => '0'], ['account' => '3100', 'debit' => '0', 'credit' => '10']], $maker), $maker);
        $this->travelTo('2027-01-15');
        $this->assertStringContainsString('waiting for approval', implode(' ', app(YearEndService::class)->blockers($this->year)));
        $this->expectException(ValidationException::class);
        app(YearEndService::class)->close($this->year, $maker);
    }

    public function test_budget_lines_approval_variance_and_the_purchase_check(): void
    {
        $org = $this->organization->id;
        $svc = app(BudgetService::class);
        $maker = $this->accountant();
        $b = $svc->create($org, $this->year, '2026 operating budget', $maker);
        $svc->setLine($b, $this->id('5200'), null, 0, '12000.00'); // 1,000 a month
        $svc->setLine($b, $this->id('4100'), null, 0, '24000.00');
        try {
            $svc->setLine($b, $this->id('1110'), null, 1, '5');
            $this->fail('budget on a balance sheet account');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        try {
            $svc->approve($b, $maker);
            $this->fail('self approval');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }
        $b = $svc->approve($b, $this->accountant());
        $this->journals()->postAuto($org, ['date' => '2026-09-02', 'memo' => 'consumables', 'source_type' => 't', 'source_id' => '1', 'event_key' => 'x'], [['account' => '5200', 'debit' => '7000', 'credit' => '0'], ['account' => '1110', 'debit' => '0', 'credit' => '7000']]);
        $this->journals()->postAuto($org, ['date' => '2026-09-03', 'memo' => 'sales', 'source_type' => 't', 'source_id' => '2', 'event_key' => 'x'], [['account' => '1110', 'debit' => '15000', 'credit' => '0'], ['account' => '4100', 'debit' => '0', 'credit' => '15000']]);
        $v = $svc->variance($b, 9);
        $exp = collect($v['rows'])->firstWhere('code', '5200');
        $this->assertSame(['9000.00', '7000.00', '2000.00'], [$exp['budget'], $exp['actual'], $exp['variance']]);
        $rev = collect($v['rows'])->firstWhere('code', '4100');
        $this->assertSame(['18000.00', '15000.00', '-3000.00'], [$rev['budget'], $rev['actual'], $rev['variance']], 'a revenue shortfall is unfavourable');
        $this->assertSame('warn', $svc->checkPurchase($org, '5200', null, '6000.00')['status']);
        $this->assertSame('ok', $svc->checkPurchase($org, '5200', null, '1000.00')['status']);
        config(['finance.budget.mode' => 'block']);
        $this->assertSame('block', $svc->checkPurchase($org, '5200', null, '6000.00')['status']);
        config(['finance.budget.mode' => 'off']);
        $this->assertSame('ok', $svc->checkPurchase($org, '5200', null, '99999.00')['status']);
        $this->expectException(ValidationException::class);
        $svc->setLine($b, $this->id('5200'), null, 1, '1'); // approved: no more edits
    }

    public function test_depreciation_runs_monthly_ends_exactly_and_never_twice(): void
    {
        $org = $this->organization->id;
        $svc = app(AssetService::class);
        $a = $svc->register($org, ['tag' => 'EQ-1', 'name' => 'Ultrasound', 'acquired_on' => '2026-01-10', 'cost' => '10000.00', 'salvage_value' => '1000.00', 'life_months' => 7]);
        $this->assertSame('1285.71', $svc->monthly($a));
        $periods = Period::query()->where('organization_id', $org)->orderBy('starts_on')->get();
        $by = $this->accountant();
        foreach ($periods->take(3) as $p) {
            $r = $svc->depreciate($org, $p, $by);
            $this->assertSame(1, $r['assets']);
        }
        $this->assertSame('3857.13', (string) $a->refresh()->accumulated);
        $again = $svc->depreciate($org, $periods[0], $by);
        $this->assertSame(0, $again['assets'], 'the same month is never charged twice');
        foreach ($periods->slice(3, 4) as $p) {
            $svc->depreciate($org, $p, $by);
        }
        $a->refresh();
        $this->assertSame('9000.00', (string) $a->accumulated, 'the last month takes what is left, so the total is exact');
        $this->assertSame('fully_depreciated', $a->status);
        $this->assertSame('1000.00', $svc->bookValue($a));
        $this->assertSame('9000.00', $this->bal('5400'));
        $this->assertSame('-9000.00', $this->bal('1590'), 'accumulated depreciation is a credit balance inside the assets');
        $this->assertSame('0.00', $svc->depreciate($org, $periods[8], $by)['amount']);
        $this->expectException(ValidationException::class);
        $svc->register($org, ['tag' => 'EQ-1', 'name' => 'dup', 'acquired_on' => '2026-01-10', 'cost' => '1', 'life_months' => 1]);
    }

    public function test_disposal_removes_cost_and_depreciation_and_books_the_difference(): void
    {
        $org = $this->organization->id;
        $svc = app(AssetService::class);
        $by = $this->accountant();
        $a = $svc->register($org, ['tag' => 'EQ-2', 'name' => 'Monitor', 'acquired_on' => '2026-01-01', 'cost' => '6000.00', 'life_months' => 6]);
        $svc->postAcquisition($a, '1120', $by);
        $svc->depreciate($org, Period::query()->where('organization_id', $org)->orderBy('starts_on')->first(), $by); // 1,000
        $svc->dispose($a, '2026-02-15', '4500.00', '1120', $by);
        $this->assertSame('0.00', $this->bal('1510'));
        $this->assertSame('0.00', $this->bal('1590'));
        $this->assertSame('500.00', $this->bal('5300'), 'sold 500 below book value 5,000');
        $this->assertSame('disposed', $a->refresh()->status);
        $this->assertSame('-1500.00', $this->bal('1120'), 'paid 6,000 and received 4,500');
    }

    public function test_doctor_fee_share_follows_the_rules_and_accrues_once(): void
    {
        $org = $this->organization->id;
        $doctor = User::factory()->create(['name' => 'Dr Rahim']);
        $invoice = $this->bill(null, [['CONSULT', '1000.00'], ['LAB', '400.00']]);
        DB::table('billing_charges')->whereIn('id', DB::table('billing_invoice_items')->where('invoice_id', $invoice->id)->pluck('charge_id'))->update(['provider_id' => $doctor->id]);
        $fees = app(FeeShareService::class);
        $fees->saveRule($org, null, null, '10');
        $fees->saveRule($org, $doctor->id, 'CONSULT', '40');
        $rev = $fees->doctorRevenue($org, '2026-09-01', '2026-09-30');
        $this->assertSame('1400.00', $rev->reduce(fn ($c, $r) => bcadd($c, $r->revenue, 2), '0.00'));
        $share = $fees->compute($org, '2026-09-01', '2026-09-30')->first();
        $this->assertSame('1400.00', $share['revenue']);
        $this->assertSame('440.00', $share['amount'], '40% of 1,000 consultation + 10% of 400 lab (the doctor+category rule beats the general one)');
        $period = app(FiscalService::class)->periodFor($org, '2026-09-10');
        $r = $fees->accrue($org, $period, $this->accountant());
        $this->assertSame(['doctors' => 1, 'amount' => '440.00'], $r);
        $this->assertSame('440.00', $this->bal('5150'));
        $this->assertSame('440.00', $this->bal('2360'));
        $this->assertSame(['doctors' => 0, 'amount' => '0.00'], $fees->accrue($org, $period, $this->accountant()));
        $this->expectException(ValidationException::class);
        $fees->saveRule($org, null, null, '150');
    }
}
