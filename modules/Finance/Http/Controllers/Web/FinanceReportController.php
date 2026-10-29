<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FeeShareRule;
use App\Models\Finance\Period;
use App\Models\User;
use App\Services\Finance\FeeShareService;
use App\Services\Finance\LedgerService;
use App\Services\Finance\StatementService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Trial balance, account ledger, the statements, profitability by department and doctor revenue with fee sharing. Every table can be downloaded (needs the export right). */
class FinanceReportController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly LedgerService $ledger, private readonly StatementService $statements, private readonly FeeShareService $fees)
    {
        parent::__construct($tenant);
    }

    public function trialBalance(Request $request)
    {
        $this->allow($request, 'finance.ledger.view');
        [$from, $to] = $this->range($request);
        $cc = $request->integer('cost_center') ?: null;
        $rows = $this->ledger->trialBalance($this->organizationId(), $from, $to, $cc);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'trial-balance', ['Code', 'Account', 'Type', 'Opening', 'Debit', 'Credit', 'Closing'], $rows->map(fn ($r) => [$r->code, $r->name, $r->type, $r->opening, $r->debit, $r->credit, $r->closing]));
        }

        return view('admin.finance.trial-balance', ['rows' => $rows, 'totals' => $this->ledger->totals($rows), 'from' => $from, 'to' => $to, 'cc' => $cc, 'centers' => CostCenter::query()->where('organization_id', $this->organizationId())->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function ledger(Request $request, Account $account)
    {
        $this->allow($request, 'finance.ledger.view');
        abort_unless($account->organization_id === $this->organizationId(), 404);
        [$from, $to] = $this->range($request);
        $l = $this->ledger->accountLedger($this->organizationId(), $account, $from, $to);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'ledger-'.$account->code, ['Date', 'Journal', 'Memo', 'Debit', 'Credit', 'Balance'], $l['lines']->map(fn ($r) => [$r->journal_date, $r->number, $r->line_memo ?: $r->memo, $r->debit, $r->credit, $r->balance]));
        }

        return view('admin.finance.ledger', ['account' => $account, 'l' => $l, 'from' => $from, 'to' => $to]);
    }

    public function incomeStatement(Request $request)
    {
        $this->allow($request, 'finance.report.view');
        [$from, $to] = $this->range($request);
        $cc = $request->integer('cost_center') ?: null;
        $r = $this->statements->incomeStatement($this->organizationId(), $from, $to, $cc);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'income-statement', ['Section', 'Code', 'Account', 'Amount'], collect($r['revenue'])->map(fn ($x) => ['Revenue', $x->code, $x->name, $x->amount])->concat(collect($r['expense'])->map(fn ($x) => ['Expense', $x->code, $x->name, $x->amount]))->push(['Result', '', 'Net profit or loss', $r['net']]));
        }

        return view('admin.finance.income-statement', ['r' => $r, 'from' => $from, 'to' => $to, 'cc' => $cc, 'centers' => CostCenter::query()->where('organization_id', $this->organizationId())->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function balanceSheet(Request $request)
    {
        $this->allow($request, 'finance.report.view');
        $on = $request->validate(['on' => ['nullable', 'date']])['on'] ?? now()->setTimezone((string) config('finance.timezone', 'Asia/Dhaka'))->toDateString();
        $r = $this->statements->balanceSheet($this->organizationId(), $on);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'balance-sheet', ['Section', 'Code', 'Account', 'Amount'], collect($r['assets'])->map(fn ($x) => ['Assets', $x->code, $x->name, $x->amount])->concat(collect($r['liabilities'])->map(fn ($x) => ['Liabilities', $x->code, $x->name, $x->amount]))
                ->concat(collect($r['equity'])->map(fn ($x) => ['Equity', $x->code, $x->name, $x->amount]))->push(['Equity', '', 'Result of the year so far', $r['current_result']]));
        }

        return view('admin.finance.balance-sheet', ['r' => $r, 'on' => $on]);
    }

    public function cashFlow(Request $request)
    {
        $this->allow($request, 'finance.report.view');
        [$from, $to] = $this->range($request);
        $r = $this->statements->cashFlow($this->organizationId(), $from, $to);
        if ($request->query('export') === 'csv') {
            $rows = [];
            foreach ($r['sections'] as $section => $items) {
                foreach ($items as $i) {
                    $rows[] = [ucfirst($section), $i['label'], $i['amount']];
                }
            }
            $rows[] = ['Total', 'Net change in cash', $r['net_change']];

            return $this->csv($request, 'cash-flow', ['Section', 'Item', 'Amount'], $rows);
        }

        return view('admin.finance.cash-flow', ['r' => $r, 'from' => $from, 'to' => $to]);
    }

    public function profitability(Request $request)
    {
        $this->allow($request, 'finance.report.view');
        [$from, $to] = $this->range($request);
        $rows = $this->statements->profitability($this->organizationId(), $from, $to);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'profitability', ['Cost centre', 'Code', 'Revenue', 'Expense', 'Result'], $rows->map(fn ($r) => [$r['cost_center'], $r['code'], $r['revenue'], $r['expense'], $r['result']]));
        }

        return view('admin.finance.profitability', ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }

    public function doctors(Request $request)
    {
        $this->allow($request, 'finance.report.view');
        [$from, $to] = $this->range($request);
        $org = $this->organizationId();
        $revenue = $this->fees->doctorRevenue($org, $from, $to);
        $shares = $this->fees->compute($org, $from, $to);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'doctor-revenue', ['Doctor', 'Revenue', 'Fee share'], $shares->map(fn ($s) => [$s['provider'], $s['revenue'], $s['amount']]));
        }

        return view('admin.finance.doctors', ['revenue' => $revenue, 'shares' => $shares, 'rules' => FeeShareRule::query()->where('organization_id', $org)->where('active', true)->get(), 'from' => $from, 'to' => $to,
            'users' => User::query()->whereIn('id', $revenue->pluck('provider_id')->filter()->unique())->pluck('name', 'id'), 'period' => Period::query()->where('organization_id', $org)->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $to)->first()]);
    }

    public function saveRule(Request $request)
    {
        $this->allow($request, 'finance.feeshare.manage');
        $d = $request->validate(['provider_user_id' => ['nullable', 'integer'], 'category_code' => ['nullable', 'string', 'max:40'], 'percent' => ['required', 'numeric'], 'note' => ['nullable', 'string', 'max:200']]);
        $this->fees->saveRule($this->organizationId(), $d['provider_user_id'] ?: null, $d['category_code'] ?: null, (string) $d['percent'], null, null, $d['note'] ?? null);

        return back()->with('success', __('Rule saved.'));
    }

    public function accrue(Request $request, Period $period)
    {
        $this->allow($request, 'finance.feeshare.manage');
        abort_unless($period->organization_id === $this->organizationId(), 404);
        $r = $this->fees->accrue($this->organizationId(), $period, $request->user());

        return back()->with('success', __(':n doctors, :a accrued.', ['n' => $r['doctors'], 'a' => $r['amount']]));
    }
}
