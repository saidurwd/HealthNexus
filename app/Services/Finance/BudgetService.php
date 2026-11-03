<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\BudgetLine;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Period;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Annual budgets by account and cost centre, spread by month, with actuals and open purchase orders (commitments) beside them. */
class BudgetService
{
    public function __construct(private readonly FinancePolicy $policy, private readonly ChartService $chart) {}

    public function create(int $organizationId, FiscalYear $year, string $name, User $by): Budget
    {
        if (Budget::query()->where(['organization_id' => $organizationId, 'fiscal_year_id' => $year->id, 'name' => $name])->exists()) {
            throw ValidationException::withMessages(['name' => 'That budget already exists for the year.']);
        }

        return Budget::create(['organization_id' => $organizationId, 'fiscal_year_id' => $year->id, 'name' => $name, 'status' => 'draft', 'created_by' => $by->id]);
    }

    /** Set a month (1-12), or pass 0 to spread an annual amount evenly. */
    public function setLine(Budget $budget, int $accountId, ?int $costCenterId, int $periodNumber, string $amount): void
    {
        if ($budget->status !== 'draft') {
            throw ValidationException::withMessages(['budget' => 'An approved budget cannot be edited; make a new one.']);
        }
        $account = Account::query()->where('organization_id', $budget->organization_id)->find($accountId);
        if ($account === null || ! in_array($account->type, ['revenue', 'expense'], true) || ! $account->postable) {
            throw ValidationException::withMessages(['account_id' => 'Budgets are set on revenue and expense accounts that take postings.']);
        }
        if (! is_numeric($amount) || bccomp($amount, '0', 2) < 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be zero or more.']);
        }
        DB::transaction(function () use ($budget, $accountId, $costCenterId, $periodNumber, $amount) {
            if ($periodNumber === 0) {
                $each = bcdiv($amount, '12', 2);
                $last = bcsub($amount, bcmul($each, '11', 2), 2);
                for ($n = 1; $n <= 12; $n++) {
                    $this->put($budget, $accountId, $costCenterId, $n, $n === 12 ? $last : $each);
                }

                return;
            }
            if ($periodNumber < 1 || $periodNumber > 12) {
                throw ValidationException::withMessages(['period' => 'The month is 1 to 12.']);
            }
            $this->put($budget, $accountId, $costCenterId, $periodNumber, $amount);
        });
    }

    private function put(Budget $b, int $account, ?int $cc, int $n, string $amount): void
    {
        BudgetLine::query()->updateOrCreate(['budget_id' => $b->id, 'account_id' => $account, 'cost_center_id' => $cc, 'period_number' => $n], ['amount' => bcadd($amount, '0', 2)]);
    }

    public function approve(Budget $budget, User $by): Budget
    {
        if ($budget->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft budget can be approved.']);
        }
        if ($budget->created_by === $by->id && ! $this->policy->bool('journal.allow_self_approval', false)) {
            throw ValidationException::withMessages(['approver' => 'A budget must be approved by someone other than the person who made it.']);
        }
        if (! BudgetLine::query()->where('budget_id', $budget->id)->exists()) {
            throw ValidationException::withMessages(['budget' => 'The budget has no lines.']);
        }
        $budget->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);

        return $budget->refresh();
    }

    /**
     * Budget against actual for the months up to and including $throughPeriod, per account and cost centre. Actual = posted movement on the account's normal side.
     * Commitments (expense accounts) are open purchase orders not yet received, when the setting is on.
     *
     * @return array{rows:list<array<string,mixed>>,totals:array{budget:string,actual:string,committed:string,variance:string}}
     */
    public function variance(Budget $budget, int $throughPeriod = 12): array
    {
        $periods = Period::query()->where('fiscal_year_id', $budget->fiscal_year_id)->where('number', '<=', $throughPeriod)->orderBy('number')->get();
        if ($periods->isEmpty()) {
            return ['rows' => [], 'totals' => ['budget' => '0.00', 'actual' => '0.00', 'committed' => '0.00', 'variance' => '0.00']];
        }
        $from = $periods->first()->starts_on->toDateString();
        $to = $periods->last()->ends_on->toDateString();
        $lines = BudgetLine::query()->where('budget_id', $budget->id)->where('period_number', '<=', $throughPeriod)->get()->groupBy(fn ($l) => $l->account_id.':'.($l->cost_center_id ?? 0));
        $commitment = $this->policy->bool('budget.commit_open_pos', true) ? $this->openOrders($budget->organization_id, $from, $to) : '0.00';
        $rows = [];
        $tot = ['budget' => '0.00', 'actual' => '0.00', 'committed' => '0.00', 'variance' => '0.00'];
        foreach ($lines as $key => $group) {
            [$accountId, $cc] = array_map('intval', explode(':', $key));
            $account = Account::query()->find($accountId);
            $planned = $group->reduce(fn ($c, $l) => bcadd($c, (string) $l->amount, 2), '0.00');
            $net = DB::table('fin_journal_lines as l')->join('fin_journals as j', 'j.id', '=', 'l.journal_id')->where('l.organization_id', $budget->organization_id)->where('j.status', 'posted')->where('j.kind', '<>', 'closing')->where('l.account_id', $accountId)
                ->when($cc, fn ($q) => $q->where('l.cost_center_id', $cc))->whereBetween('l.journal_date', [$from, $to])->selectRaw('coalesce(sum(l.debit), 0) as d, coalesce(sum(l.credit), 0) as c')->first();
            $actual = $account->normal_side === 'debit' ? bcsub((string) $net->d, (string) $net->c, 2) : bcsub((string) $net->c, (string) $net->d, 2);
            $committed = $account->type === 'expense' && $account->code === $this->chart->accountFor($budget->organization_id, 'stock_expense')->code ? $commitment : '0.00';
            // an expense over budget is bad, a revenue shortfall is bad: variance is shown as "favourable if positive"
            $variance = $account->type === 'expense' ? bcsub($planned, bcadd($actual, $committed, 2), 2) : bcsub($actual, $planned, 2);
            $rows[] = ['account_id' => $accountId, 'code' => $account->code, 'name' => $account->name, 'type' => $account->type, 'cost_center_id' => $cc ?: null, 'budget' => $planned, 'actual' => $actual, 'committed' => $committed, 'variance' => $variance];
            foreach (['budget' => $planned, 'actual' => $actual, 'committed' => $committed, 'variance' => $variance] as $k => $v) {
                $tot[$k] = bcadd($tot[$k], $v, 2);
            }
        }
        usort($rows, fn ($a, $b) => strcmp($a['code'], $b['code']));

        return ['rows' => $rows, 'totals' => $tot];
    }

    /** Value of purchase orders issued but not yet fully received (what the hospital has promised to pay). */
    public function openOrders(int $organizationId, string $from, string $to): string
    {
        return bcadd((string) ProcurementPurchaseOrder::query()->where('organization_id', $organizationId)->whereIn('status', ['approved', 'issued', 'partially_received'])->whereBetween('order_date', [$from, $to])->sum('total'), '0', 2);
    }

    /**
     * The check a purchase can ask before it is placed: how much of the expense budget is left for a cost centre / account this year.
     *
     * @return array{status:string,left:?string,message:string}
     */
    public function checkPurchase(int $organizationId, string $accountCode, ?int $costCenterId, string $amount, ?string $on = null): array
    {
        $mode = (string) $this->policy->get('budget.mode', 'warn');
        if ($mode === 'off') {
            return ['status' => 'ok', 'left' => null, 'message' => 'Budget checking is off.'];
        }
        $on ??= now()->toDateString();
        $year = FiscalYear::query()->where('organization_id', $organizationId)->where('starts_on', '<=', $on)->where('ends_on', '>=', $on)->first();
        $account = Account::query()->where(['organization_id' => $organizationId, 'code' => $accountCode])->first();
        $budget = $year ? Budget::query()->where(['organization_id' => $organizationId, 'fiscal_year_id' => $year->id, 'status' => 'approved'])->first() : null;
        if ($budget === null || $account === null) {
            return ['status' => 'ok', 'left' => null, 'message' => 'No approved budget covers this purchase.'];
        }
        $row = collect($this->variance($budget, 12)['rows'])->first(fn ($r) => $r['account_id'] === $account->id && ($costCenterId === null || $r['cost_center_id'] === $costCenterId));
        if ($row === null) {
            return ['status' => 'ok', 'left' => null, 'message' => 'This account has no budget line.'];
        }
        $left = $row['variance'];
        if (bccomp($amount, $left, 2) <= 0) {
            return ['status' => 'ok', 'left' => $left, 'message' => "Within budget ($left left)."];
        }
        $status = $mode === 'block' ? 'block' : 'warn';

        return ['status' => $status, 'left' => $left, 'message' => "This purchase of $amount is more than the $left left in the budget."];
    }
}
