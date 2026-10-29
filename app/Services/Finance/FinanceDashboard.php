<?php

namespace App\Services\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankStatementLine;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Journal;
use App\Models\Finance\Payable;
use App\Models\Finance\Posting;
use App\Models\Finance\Receivable;
use Illuminate\Support\Facades\DB;

/** The figures a finance manager looks at first. */
class FinanceDashboard
{
    public function __construct(private readonly StatementService $statements, private readonly BankService $banks, private readonly ReconciliationService $reconciliation, private readonly FiscalService $fiscal) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId): array
    {
        $today = now()->setTimezone((string) config('finance.timezone', 'Asia/Dhaka'));
        $from = $today->copy()->startOfMonth()->toDateString();
        $to = $today->toDateString();
        $period = $this->fiscal->periodFor($organizationId, $to);
        $is = $this->statements->incomeStatement($organizationId, $from, $to);
        $banks = BankAccount::query()->where('organization_id', $organizationId)->where('active', true)->orderBy('kind')->orderBy('name')->get()->map(fn (BankAccount $b) => ['bank' => $b, 'balance' => $this->banks->ledgerBalance($b)]);
        $arLeft = (string) Receivable::query()->where('organization_id', $organizationId)->where('status', 'open')->sum(DB::raw('amount - settled - written_off'));
        $apLeft = (string) Payable::query()->where('organization_id', $organizationId)->where('status', 'open')->sum(DB::raw('amount - settled'));
        $recon = $this->reconciliation->billingVsLedger($organizationId, $from, $to);

        return [
            'ready' => FiscalYear::query()->where('organization_id', $organizationId)->exists(),
            'month' => $today->format('F Y'), 'from' => $from, 'to' => $to, 'period' => $period,
            'revenue' => $is['total_revenue'], 'expense' => $is['total_expense'], 'net' => $is['net'],
            'banks' => $banks, 'cash_total' => $banks->reduce(fn ($c, $r) => bcadd($c, $r['balance'], 2), '0.00'),
            'receivable' => bcadd($arLeft, '0', 2), 'payable' => bcadd($apLeft, '0', 2),
            'failed' => Posting::query()->where(['organization_id' => $organizationId, 'status' => 'failed'])->count(),
            'drafts' => Journal::query()->where(['organization_id' => $organizationId, 'status' => 'draft'])->count(),
            'pending' => Journal::query()->where(['organization_id' => $organizationId, 'status' => 'pending'])->count(),
            'unmatched_lines' => BankStatementLine::query()->where('status', 'unmatched')->whereIn('statement_id', DB::table('fin_bank_statements')->where('organization_id', $organizationId)->select('id'))->count(),
            'recon' => $recon,
        ];
    }
}
