<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingCashierSession;
use App\Models\Department;
use App\Models\Finance\Account;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankDeposit;
use App\Models\Finance\BankStatementLine;
use App\Models\Finance\Budget;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Payable;
use App\Models\Finance\Period;
use App\Models\Finance\Posting;
use App\Models\Finance\Receivable;
use App\Models\Finance\SupplierPayment;
use App\Models\Hr\HrPayrollRun;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Finance\AssetService;
use App\Services\Finance\BankService;
use App\Services\Finance\BudgetService;
use App\Services\Finance\ChartService;
use App\Services\Finance\FeeShareService;
use App\Services\Finance\FiscalService;
use App\Services\Finance\JournalService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\PayableService;
use App\Services\Finance\PostingEngine;
use App\Services\Finance\ReceivableService;
use App\Services\Finance\StatementService;
use App\Services\Finance\StockPostingService;
use App\Services\Finance\TaxService;
use App\Services\Hr\PayrollJournalService;
use App\Services\Inventory\SupplierInvoiceMatchingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The books for six months. The accountant starts the books (chart, fiscal years, cost centres per department, bank and till accounts, tax codes, an opening balance), and everything the other
 * modules did so far is posted through the posting engine with its own dates: invoices, payments, refunds, claims, goods received, stock used, payroll, supplier invoices. On top come the things people do:
 * tills banked, online-payment settlements, suppliers paid with a second person's approval, rent and utilities by journal (one sent back, one reversed), old patient debts written off, monthly bank
 * statements matched (the latest month left open), a budget, fixed assets depreciated month by month, doctors' fee shares, and the older periods locked. It ends by checking the books balance.
 */
class FinanceHistory implements DemoHistoryModule
{
    /** @var array<string,int> */
    private array $n = ['journals' => 0, 'supplier_payments' => 0, 'deposits' => 0, 'statements' => 0, 'writeoffs' => 0, 'assets' => 0];

    public function __construct(
        private readonly ChartService $chart,
        private readonly FiscalService $fiscal,
        private readonly JournalService $journals,
        private readonly PostingEngine $engine,
        private readonly StockPostingService $stock,
        private readonly BankService $banks,
        private readonly PayableService $payables,
        private readonly ReceivableService $receivables,
        private readonly BudgetService $budgets,
        private readonly AssetService $assets,
        private readonly FeeShareService $fees,
        private readonly TaxService $tax,
        private readonly LedgerService $ledger,
        private readonly StatementService $statements,
    ) {}

    public function name(): string
    {
        return 'Finance and accounts';
    }

    public function module(): string
    {
        return 'finance';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $maker = $context->staff('accounts_officer')->first();
        $checker = $context->staff('accountant')->first();
        $manager = $context->staff('finance_manager')->first();

        return $context->at($context->to->setTime(18, 0), function () use ($context, $org, $maker, $checker, $manager) {
            $this->setUp($context, $org, $maker, $checker);
            $this->postWhatHappened($context, $org);
            $bank = BankAccount::query()->where(['organization_id' => $org, 'kind' => 'bank'])->orderBy('id')->first();
            $this->bankTills($context, $org, $bank, $manager);
            $this->settleOnlinePayments($org, $bank, $manager);
            $this->paySuppliers($context, $org, $bank, $maker, $checker, $manager);
            $this->manualJournals($context, $org, $maker, $checker);
            $this->writeOffOldDebts($context, $org, $maker, $checker);
            $this->assetsAndDepreciation($context, $org, $maker, $checker);
            $this->feeShares($context, $org, $manager);
            $this->budget($context, $org, $maker, $checker);
            $this->statements($context, $org, $bank, $maker, $manager);
            $this->lockOldPeriods($context, $org, $manager);

            $tb = $this->ledger->trialBalance($org, null, $context->to->toDateString());
            if (! $this->ledger->totals($tb)['balanced']) {
                throw new RuntimeException('The books built by the demo do not balance.');
            }
            $is = $this->statements->incomeStatement($org, $context->from->toDateString(), $context->to->toDateString());

            return sprintf('%d journals posted (%d by hand), %d tills banked, %d supplier payments, %d bank statements, %d write-offs, %d assets; revenue %s, expenses %s, result %s; %d postings still failed',
                Journal::query()->where(['organization_id' => $org, 'status' => 'posted'])->count(), $this->n['journals'], $this->n['deposits'], $this->n['supplier_payments'], $this->n['statements'], $this->n['writeoffs'], $this->n['assets'],
                number_format((float) $is['total_revenue']), number_format((float) $is['total_expense']), number_format((float) $is['net']), Posting::query()->where(['organization_id' => $org, 'status' => 'failed'])->count());
        });
    }

    // ---- 1. start the books -----------------------------------------------------------------------------------------------------------------

    private function setUp(HistoryContext $c, int $org, $maker, $checker): void
    {
        $this->chart->seedStarter($org);
        foreach (range($c->from->year, $c->to->year) as $year) {
            if (! FiscalYear::query()->where('organization_id', $org)->whereDate('starts_on', '<=', "$year-12-31")->whereDate('ends_on', '>=', "$year-01-01")->exists()) {
                $this->fiscal->createYear($org, $year, 1);
            }
        }
        foreach (Department::query()->where('organization_id', $org)->orderBy('id')->get() as $d) {
            $this->chart->createCostCenter($org, Str::upper(Str::limit(Str::slug($d->name, ''), 8, '')).$d->id, $d->name, 'profit', null, null, $d->id);
        }
        $ledgerId = fn (string $code) => (int) Account::query()->where(['organization_id' => $org, 'code' => $code])->value('id');
        $this->banks->createAccount($org, 'Cashier tills', 'cash', $ledgerId('1110'), null, null, 'CASH');
        $this->banks->createAccount($org, 'City Bank current account', 'bank', $ledgerId('1120'), 'City Bank', '4821', 'BANK');
        $this->banks->createAccount($org, 'bKash merchant wallet', 'wallet', $ledgerId('1130'), 'bKash', null, 'MFS');
        $this->banks->createAccount($org, 'Card and online payments clearing', 'gateway', $ledgerId('1140'), null, null, 'CARD');
        foreach ([['VAT-OUT', 'VAT on sales', 'vat_output', '2300'], ['VAT-IN', 'VAT on purchases', 'vat_input', '1450'], ['AIT', 'AIT / TDS withheld', 'ait', '2310']] as [$code, $name, $kind, $acct]) {
            $this->tax->saveCode($org, ['code' => $code, 'name' => $name, 'kind' => $kind, 'rate' => '0', 'account_id' => $ledgerId($acct), 'note' => 'Demo: the rate is set by the accountant']);
        }
        // opening position on the first day: money in the bank, the starting capital
        $day = $c->from->subDays(1)->toDateString();
        $period = $this->fiscal->periodFor($org, $day) ?? $this->fiscal->periodFor($org, $c->from);
        $date = $period->starts_on->lte($day) ? $day : $c->from->toDateString();
        $j = $this->journals->createDraft($org, ['date' => $date, 'memo' => 'Opening balances', 'kind' => 'opening'], [
            ['account' => '1120', 'debit' => '25000000', 'credit' => '0'], ['account' => '1110', 'debit' => '250000', 'credit' => '0'], ['account' => '3900', 'debit' => '0', 'credit' => '25250000'],
        ], $maker);
        $this->journals->approve($this->journals->submit($j, $maker), $checker);
        $this->n['journals']++;
    }

    // ---- 2. post what the other modules did -------------------------------------------------------------------------------------------------

    private function postWhatHappened(HistoryContext $c, int $org): void
    {
        $days = (int) ($c->from->diffInDays($c->to) + 10);
        for ($i = 0; $i < 60; $i++) {
            $done = array_sum($this->engine->catchUp($org, $days)) + $this->stock->postReceipts($org);
            if ($done === 0) {
                break;
            }
        }
        // stock used so far
        for ($i = 0; $i < 40 && $this->stock->postMovements($org)['rows'] > 0; $i++) {
            // each call takes the next slice of the stock ledger
        }
        // payroll runs approved earlier have a journal waiting for the books; invoices from suppliers the same
        foreach (HrPayrollRun::query()->where('organization_id', $org)->whereIn('status', ['approved', 'paid'])->whereNull('finance_reference')->whereNotNull('journal')->orderBy('id')->get() as $run) {
            app(PayrollJournalService::class)->post($run);
        }
        foreach (ProcurementSupplierInvoice::query()->where('organization_id', $org)->whereIn('status', ['matched', 'approved'])->whereNull('finance_reference')->orderBy('id')->get() as $inv) {
            app(SupplierInvoiceMatchingService::class)->handToFinance($inv);
        }
        $this->engine->retryFailed($org, 500);
    }

    // ---- 3. money moves ------------------------------------------------------------------------------------------------------------------------

    private function bankTills(HistoryContext $c, int $org, ?BankAccount $bank, $by): void
    {
        if ($bank === null) {
            return;
        }
        foreach (BillingCashierSession::query()->where('organization_id', $org)->where('status', 'closed')->orderBy('closed_at')->get() as $session) {
            if (BankDeposit::query()->where('cashier_session_id', $session->id)->exists()) {
                continue;
            }
            try {
                $this->banks->depositSession($session, $bank, $by);
                $this->n['deposits']++;
            } catch (ValidationException) {
                // a session with nothing to bank
            }
        }
    }

    private function settleOnlinePayments(int $org, ?BankAccount $bank, $by): void
    {
        if ($bank === null) {
            return;
        }
        foreach (DB::table('portal_settlements')->where('organization_id', $org)->orderBy('id')->get() as $s) {
            if (! Journal::query()->where(['organization_id' => $org, 'source_type' => 'portal_settlement', 'source_id' => (string) $s->id])->exists()) {
                $this->banks->settleGateway($org, $s->id, $bank, $by);
            }
        }
    }

    private function paySuppliers(HistoryContext $c, int $org, ?BankAccount $bank, $maker, $checker, $manager): void
    {
        if ($bank === null) {
            return;
        }
        foreach (Payable::query()->where(['organization_id' => $org, 'status' => 'open'])->whereDate('due_date', '<=', $c->to->subDays(3)->toDateString())->orderBy('due_date')->get() as $p) {
            if (! $c->chance(75)) {
                continue;
            }
            $date = max($p->due_date->toDateString(), $p->invoice_date->toDateString());
            $amount = $this->payables->outstanding($p);
            $ait = $c->chance(60) ? bcdiv(bcmul($amount, '3', 2), '100', 2) : '0'; // demo figure, not a real rate
            $pay = $this->payables->createPayment($org, ['supplier_id' => $p->supplier_id, 'payment_date' => $date, 'method' => 'bank', 'bank_account_id' => $bank->id, 'withheld_ait' => $ait, 'withheld_vat' => '0', 'reference' => 'TRF'.mt_rand(100000, 999999)], [['payable_id' => $p->id, 'amount' => $amount]], $maker);
            $this->payables->pay($this->payables->approvePayment($pay, $checker), $manager);
            $this->n['supplier_payments']++;
        }
    }

    private function manualJournals(HistoryContext $c, int $org, $maker, $checker): void
    {
        $month = $c->from->startOfMonth();
        $cc = fn () => null;
        while ($month->lte($c->to)) {
            $date = $month->addDays(4)->min($c->to)->toDateString();
            foreach ([['5800', 'Hospital building rent '.$month->format('M Y'), '450000'], ['5800', 'Electricity and water '.$month->format('M Y'), (string) (180000 + mt_rand(0, 60000))]] as [$expense, $memo, $amount]) {
                $j = $this->journals->createDraft($org, ['date' => $date, 'memo' => $memo], [['account' => $expense, 'debit' => $amount, 'credit' => '0'], ['account' => '1120', 'debit' => '0', 'credit' => $amount]], $maker);
                $j = $this->journals->submit($j, $maker);
                if ($c->chance(12)) { // sent back once, then fixed and approved
                    $j = $this->journals->reject($j, $checker, 'Please add the bill number to the memo.');
                    $this->journals->replaceLines($j, [['account' => $expense, 'debit' => $amount, 'credit' => '0'], ['account' => '1120', 'debit' => '0', 'credit' => $amount]], ['date' => $date, 'memo' => $memo.' (bill attached)']);
                    $j = $this->journals->submit($j->refresh(), $maker);
                }
                $this->journals->approve($j, $checker);
                $this->n['journals']++;
            }
            $month = $month->addMonth();
        }
        // one entry posted to the wrong account and reversed
        $wrong = $this->journals->createDraft($org, ['date' => $c->to->subDays(20)->toDateString(), 'memo' => 'Staff training fee'], [['account' => '5100', 'debit' => '35000', 'credit' => '0'], ['account' => '1120', 'debit' => '0', 'credit' => '35000']], $maker);
        $wrong = $this->journals->approve($this->journals->submit($wrong, $maker), $checker);
        $this->journals->reverse($wrong, $checker, 'Posted to salaries by mistake; booked to running costs instead.', $c->to->subDays(19));
        $fix = $this->journals->createDraft($org, ['date' => $c->to->subDays(19)->toDateString(), 'memo' => 'Staff training fee (corrected)'], [['account' => '5800', 'debit' => '35000', 'credit' => '0'], ['account' => '1120', 'debit' => '0', 'credit' => '35000']], $maker);
        $this->journals->approve($this->journals->submit($fix, $maker), $checker);
        $this->n['journals'] += 3;
    }

    private function writeOffOldDebts(HistoryContext $c, int $org, $maker, $checker): void
    {
        $old = Receivable::query()->where(['organization_id' => $org, 'status' => 'open', 'party_type' => 'patient'])->whereDate('due_date', '<=', $c->to->subDays(75)->toDateString())->orderBy('due_date')->limit(40)->get();
        foreach ($old as $r) {
            if ($this->n['writeoffs'] >= 6 || ! $c->chance(15)) {
                continue;
            }
            $left = $this->receivables->outstanding($r);
            if (bccomp($left, '0', 2) <= 0) {
                continue;
            }
            $w = $this->receivables->requestWriteoff($r, $left, 'Patient could not be traced after three reminders.', $maker);
            $this->receivables->decideWriteoff($w, true, $checker, 'Checked the reminders.');
            $this->n['writeoffs']++;
        }
    }

    private function assetsAndDepreciation(HistoryContext $c, int $org, $maker, $checker): void
    {
        $start = $c->from->toDateString();
        $list = [['EQ-001', 'Digital X-ray system', '8500000', 96], ['EQ-002', 'Ultrasound machine', '3200000', 84], ['EQ-003', 'Patient monitors (10)', '1800000', 60], ['EQ-004', 'Laboratory analyser', '4200000', 84], ['EQ-005', 'Ambulance', '5200000', 96], ['EQ-006', 'Generator 500 kVA', '2900000', 120]];
        foreach ($list as [$tag, $name, $cost, $life]) {
            $a = $this->assets->register($org, ['tag' => $tag, 'name' => $name, 'acquired_on' => $start, 'cost' => $cost, 'salvage_value' => bcmul($cost, '0.05', 2), 'life_months' => $life, 'depreciation_from' => $start], $maker);
            $this->assets->postAcquisition($a, '3900', $maker);
            $this->n['assets']++;
        }
        foreach (Period::query()->where('organization_id', $org)->whereDate('ends_on', '<', $c->to->toDateString())->whereDate('ends_on', '>=', $start)->orderBy('starts_on')->get() as $p) {
            if ($p->status === 'open') {
                $this->assets->depreciate($org, $p, $checker);
            }
        }
        // one old monitor is sold
        $sold = $this->assets->register($org, ['tag' => 'EQ-007', 'name' => 'Old ECG machine', 'acquired_on' => $start, 'cost' => '600000', 'salvage_value' => '0', 'life_months' => 60, 'depreciation_from' => $start], $maker);
        $this->assets->postAcquisition($sold, '3900', $maker);
        $this->assets->dispose($sold, $c->to->subDays(10)->toDateString(), '350000', '1120', $checker);
    }

    private function feeShares(HistoryContext $c, int $org, $manager): void
    {
        $this->fees->saveRule($org, null, null, '10', null, null, 'Demo: every doctor, every service');
        foreach (Period::query()->where('organization_id', $org)->whereDate('ends_on', '<', $c->to->toDateString())->whereDate('ends_on', '>=', $c->from->toDateString())->orderBy('starts_on')->get() as $p) {
            $this->fees->accrue($org, $p, $manager);
        }
    }

    private function budget(HistoryContext $c, int $org, $maker, $checker): void
    {
        $year = FiscalYear::query()->where('organization_id', $org)->whereDate('starts_on', '<=', $c->to->toDateString())->whereDate('ends_on', '>=', $c->to->toDateString())->first();
        if ($year === null) {
            return;
        }
        $b = $this->budgets->create($org, $year, $year->name.' operating budget', $maker);
        $is = $this->statements->incomeStatement($org, $c->from->toDateString(), $c->to->toDateString());
        $months = max(1, (int) round($c->from->diffInMonths($c->to)));
        foreach ([...$is['revenue'], ...$is['expense']] as $line) {
            if (bccomp($line->amount, '0', 2) > 0) {
                $yearly = bcmul(bcdiv($line->amount, (string) $months, 4), '12', 2);
                $this->budgets->setLine($b, $line->account_id, null, 0, bcmul($yearly, '1.08', 2));
            }
        }
        $this->budgets->approve($b, $checker);
    }

    // ---- 4. bank statements --------------------------------------------------------------------------------------------------------------------

    private function statements(HistoryContext $c, int $org, ?BankAccount $bank, $maker, $manager): void
    {
        if ($bank === null) {
            return;
        }
        $month = $c->from->startOfMonth();
        while ($month->endOfMonth()->lt($c->to)) {
            $from = $month->toDateString();
            $to = $month->endOfMonth()->toDateString();
            $rows = [];
            foreach (BankDeposit::query()->where(['organization_id' => $org, 'bank_account_id' => $bank->id])->whereBetween('deposit_date', [$from, $to])->get() as $d) {
                $rows[] = [$d->deposit_date->addDay()->min($month->endOfMonth())->toDateString(), 'Cash deposit '.$d->number, $d->number, (string) $d->amount, ''];
            }
            foreach (SupplierPayment::query()->where(['organization_id' => $org, 'bank_account_id' => $bank->id, 'status' => 'paid'])->whereBetween('payment_date', [$from, $to])->get() as $p) {
                $rows[] = [$p->payment_date->toDateString(), 'Transfer '.$p->number, (string) $p->reference, '', (string) $p->net_amount];
            }
            foreach (Journal::query()->where(['organization_id' => $org, 'source_type' => 'portal_settlement'])->whereBetween('journal_date', [$from, $to])->get() as $j) {
                $rows[] = [$j->journal_date->toDateString(), 'Online payments settlement', 'SET'.$j->source_id, (string) bcsub((string) $j->total_credit, (string) DB::table('portal_settlements')->where('id', $j->source_id)->value('fees'), 2), ''];
            }
            $interest = (string) mt_rand(900, 2400);
            $rows[] = [$to, 'Interest', 'INT', $interest, ''];
            $rows[] = [$to, 'Monthly account fee', 'FEE', '', '500.00'];
            $ledgerAtEnd = $this->banks->ledgerBalance($bank, $to);
            $closing = bcsub(bcadd($ledgerAtEnd, $interest, 2), '500.00', 2);
            $csv = "Date,Description,Reference,In,Out\n".collect($rows)->map(fn ($r) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $r)))->implode("\n");
            $res = $this->banks->importStatement($bank, $csv, 'statement-'.$month->format('Y-m').'.csv', $maker, null, $closing);
            $this->banks->autoMatch($res['statement'], $maker);
            $statement = $res['statement']->refresh();
            $isLast = $month->endOfMonth()->addMonth()->gte($c->to);
            foreach (BankStatementLine::query()->where(['statement_id' => $statement->id, 'status' => 'unmatched'])->get() as $line) {
                if ($isLast) {
                    break; // the latest month is left for the accountant to work on
                }
                $this->banks->postLine($line, $line->description === 'Interest' ? '4700' : '5600', $maker, $line->description);
            }
            if (! $isLast && $this->banks->summary($statement)['reconciled']) {
                $this->banks->markReconciled($statement, $manager);
            }
            $this->n['statements']++;
            $month = $month->addMonth();
        }
    }

    // ---- 5. close the old months ---------------------------------------------------------------------------------------------------------------

    private function lockOldPeriods(HistoryContext $c, int $org, $manager): void
    {
        foreach (Period::query()->where('organization_id', $org)->where('status', 'open')->whereDate('ends_on', '<', $c->to->subDays(40)->toDateString())->orderBy('starts_on')->get() as $p) {
            $this->fiscal->lock($p, $manager, 'Month checked and closed');
        }
    }
}
