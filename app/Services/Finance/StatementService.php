<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** The financial statements, built only from posted journals: income statement, balance sheet, cash flow and profit by cost centre. */
class StatementService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Revenue and expense for a range. Positive net = profit.
     *
     * @return array<string, mixed> revenue and expense lines (code, name, amount), total_revenue, total_expense, net
     */
    public function incomeStatement(int $organizationId, string $from, string $to, ?int $costCenterId = null): array
    {
        $rows = $this->ledger->trialBalance($organizationId, $from, $to, $costCenterId, false, true)->filter(fn ($r) => in_array($r->type, ['revenue', 'expense'], true));
        $line = fn ($r) => (object) ['account_id' => $r->account_id, 'code' => $r->code, 'name' => $r->name, 'amount' => $r->normal_side === 'debit' ? bcsub($r->debit, $r->credit, 2) : bcsub($r->credit, $r->debit, 2)];
        $revenue = $rows->where('type', 'revenue')->map($line)->values();
        $expense = $rows->where('type', 'expense')->map($line)->values();
        $tr = $revenue->reduce(fn ($c, $r) => bcadd($c, $r->amount, 2), '0.00');
        $te = $expense->reduce(fn ($c, $r) => bcadd($c, $r->amount, 2), '0.00');

        return ['revenue' => $revenue, 'expense' => $expense, 'total_revenue' => $tr, 'total_expense' => $te, 'net' => bcsub($tr, $te, 2)];
    }

    /**
     * Assets = liabilities + equity (+ the result of years not yet closed). `balanced` must be true in sound books.
     *
     * @return array<string, mixed> assets, liabilities, equity lines, their totals, current_result and balanced
     */
    public function balanceSheet(int $organizationId, string $asOf): array
    {
        $tb = $this->ledger->trialBalance($organizationId, null, $asOf);
        $line = fn ($r) => (object) ['account_id' => $r->account_id, 'code' => $r->code, 'name' => $r->name, 'amount' => $r->closing];
        $sum = fn (Collection $c) => $c->reduce(fn ($a, $r) => bcadd($a, $r->amount, 2), '0.00');
        $assets = $tb->where('type', 'asset')->map($line)->values();
        $liabilities = $tb->where('type', 'liability')->map($line)->values();
        $equity = $tb->where('type', 'equity')->map($line)->values();
        $result = '0.00'; // profit of all revenue/expense not yet swept to retained earnings
        foreach ($tb->filter(fn ($r) => in_array($r->type, ['revenue', 'expense'], true)) as $r) {
            $result = $r->type === 'revenue' ? bcadd($result, $r->closing, 2) : bcsub($result, $r->closing, 2);
        }
        $ta = $sum($assets);
        $tl = $sum($liabilities);
        $te = $sum($equity);

        return ['assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity, 'total_assets' => $ta, 'total_liabilities' => $tl, 'total_equity' => $te, 'current_result' => $result,
            'balanced' => bccomp($ta, bcadd(bcadd($tl, $te, 2), $result, 2), 2) === 0];
    }

    /**
     * Cash flow by the indirect method: profit, adjusted for depreciation and changes in receivables, stock and payables (operating), purchases of fixed assets (investing) and equity (financing).
     * It must explain the whole change in cash and bank accounts (`reconciles`).
     *
     * @return array{result:string,sections:array<string,list<array{label:string,amount:string}>>,operating:string,investing:string,financing:string,net_change:string,opening_cash:string,closing_cash:string,reconciles:bool}
     */
    public function cashFlow(int $organizationId, string $from, string $to): array
    {
        $before = CarbonImmutable::parse($from)->subDay()->toDateString();
        $open = $this->ledger->trialBalance($organizationId, null, $before, null, true, true)->keyBy('account_id');
        $close = $this->ledger->trialBalance($organizationId, null, $to, null, true, true)->keyBy('account_id');
        $accounts = Account::query()->where('organization_id', $organizationId)->get();
        $delta = function (Account $a) use ($open, $close) {
            $o = $open->get($a->id)->closing ?? '0.00';
            $c = $close->get($a->id)->closing ?? '0.00';

            return bcsub($c, $o, 2); // on the account's own side
        };
        $result = $this->incomeStatement($organizationId, $from, $to)['net'];
        $operating = [['label' => 'Profit for the period', 'amount' => $result]];
        $investing = [];
        $financing = [];
        $cashDelta = '0.00';
        $depr = '0.00';
        foreach ($accounts as $a) {
            $d = $delta($a);
            if (bccomp($d, '0', 2) === 0 || in_array($a->type, ['revenue', 'expense'], true)) {
                continue;
            }
            $signed = $a->normal_side === 'debit' ? $d : bcmul($d, '-1', 2); // debit-positive change
            if (in_array($a->control, ['cash', 'bank'], true)) {
                $cashDelta = bcadd($cashDelta, $signed, 2);

                continue;
            }
            if ($a->code === '1590' || str_starts_with($a->name, 'Accumulated depreciation')) { // the non-cash charge is added back
                $depr = bcadd($depr, bcmul($signed, '-1', 2), 2);

                continue;
            }
            $effect = bcmul($signed, '-1', 2); // an increase in an asset uses cash; an increase in a liability or equity brings cash
            if ($a->type === 'equity') {
                $financing[] = ['label' => $a->name, 'amount' => $effect];
            } elseif ($a->type === 'asset' && str_starts_with($a->code, '15')) {
                $investing[] = ['label' => $a->name, 'amount' => $effect];
            } else {
                $operating[] = ['label' => ($a->type === 'asset' ? 'Increase in ' : 'Increase in ').$a->name, 'amount' => $effect];
            }
        }
        bccomp($depr, '0', 2) !== 0 && array_splice($operating, 1, 0, [['label' => 'Depreciation (not a cash cost)', 'amount' => $depr]]);
        $sum = fn (array $l) => array_reduce($l, fn ($c, $r) => bcadd($c, $r['amount'], 2), '0.00');
        $op = $sum($operating);
        $inv = $sum($investing);
        $fin = $sum($financing);
        $net = bcadd(bcadd($op, $inv, 2), $fin, 2);
        $cashOpen = $this->cashBalance($organizationId, $open, $accounts);
        $cashClose = $this->cashBalance($organizationId, $close, $accounts);

        return ['result' => $result, 'sections' => ['operating' => $operating, 'investing' => $investing, 'financing' => $financing], 'operating' => $op, 'investing' => $inv, 'financing' => $fin, 'net_change' => $net, 'opening_cash' => $cashOpen, 'closing_cash' => $cashClose,
            'reconciles' => bccomp($net, bcsub($cashClose, $cashOpen, 2), 2) === 0 && bccomp($cashDelta, $net, 2) === 0];
    }

    private function cashBalance(int $organizationId, Collection $tb, Collection $accounts): string
    {
        $sum = '0.00';
        foreach ($accounts->filter(fn ($a) => in_array($a->control, ['cash', 'bank'], true)) as $a) {
            $sum = bcadd($sum, $a->normal_side === 'debit' ? ($tb->get($a->id)->closing ?? '0.00') : bcmul($tb->get($a->id)->closing ?? '0.00', '-1', 2), 2);
        }

        return $sum;
    }

    /**
     * Revenue, expense and result by cost centre for a range (a department or branch is a cost centre).
     *
     * @return Collection<int, mixed> rows of cost_center, code, kind, revenue, expense, result
     */
    public function profitability(int $organizationId, string $from, string $to): Collection
    {
        $rows = DB::table('fin_journal_lines as l')->join('fin_journals as j', 'j.id', '=', 'l.journal_id')->join('fin_accounts as a', 'a.id', '=', 'l.account_id')->leftJoin('fin_cost_centers as c', 'c.id', '=', 'l.cost_center_id')
            ->where('l.organization_id', $organizationId)->where('j.status', 'posted')->where('j.kind', '<>', 'closing')->whereBetween('l.journal_date', [$from, $to])->whereIn('a.type', ['revenue', 'expense'])
            ->groupBy('c.id', 'c.name', 'c.code', 'c.kind', 'a.type')->selectRaw('c.id as cid, c.name as cname, c.code as ccode, c.kind as ckind, a.type as atype, coalesce(sum(l.debit),0) as d, coalesce(sum(l.credit),0) as cr')->get();
        $out = [];
        foreach ($rows as $r) {
            $k = $r->cid ?? 0;
            $out[$k] ??= ['cost_center' => $r->cname ?? 'Not assigned to a cost centre', 'code' => $r->ccode ?? '—', 'kind' => $r->ckind ?? '', 'revenue' => '0.00', 'expense' => '0.00', 'result' => '0.00'];
            if ($r->atype === 'revenue') {
                $out[$k]['revenue'] = bcadd($out[$k]['revenue'], bcsub((string) $r->cr, (string) $r->d, 2), 2);
            } else {
                $out[$k]['expense'] = bcadd($out[$k]['expense'], bcsub((string) $r->d, (string) $r->cr, 2), 2);
            }
        }
        foreach ($out as &$o) {
            $o['result'] = bcsub($o['revenue'], $o['expense'], 2);
        }

        return collect($out)->sortByDesc(fn ($o) => (float) $o['revenue'])->values();
    }

    public function costCenters(int $organizationId): Collection
    {
        return CostCenter::query()->where('organization_id', $organizationId)->orderBy('code')->get();
    }
}
