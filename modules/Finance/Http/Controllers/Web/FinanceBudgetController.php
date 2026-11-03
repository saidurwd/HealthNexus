<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FiscalYear;
use App\Services\Finance\BudgetService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Annual budgets by account and cost centre, with actuals and committed purchase orders beside them. */
class FinanceBudgetController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly BudgetService $budgets)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.budget.view');
        $org = $this->organizationId();

        return view('admin.finance.budgets', ['rows' => Budget::query()->where('organization_id', $org)->latest('id')->get(), 'years' => FiscalYear::query()->where('organization_id', $org)->orderByDesc('starts_on')->get(), 'yearNames' => FiscalYear::query()->where('organization_id', $org)->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'finance.budget.manage');
        $d = $request->validate(['fiscal_year_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:120']]);
        $year = FiscalYear::query()->where('organization_id', $this->organizationId())->findOrFail($d['fiscal_year_id']);
        $b = $this->budgets->create($this->organizationId(), $year, $d['name'], $request->user());

        return redirect()->route('admin.finance.budgets.show', $b->public_id)->with('success', __('Budget created.'));
    }

    public function show(Request $request, string $budget)
    {
        $this->allow($request, 'finance.budget.view');
        $b = $this->find(Budget::class, $budget);
        $org = $this->organizationId();
        $through = max(1, min(12, $request->integer('through', 12)));
        $v = $this->budgets->variance($b, $through);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'budget-variance', ['Code', 'Account', 'Budget', 'Actual', 'Committed', 'Variance'], collect($v['rows'])->map(fn ($r) => [$r['code'], $r['name'], $r['budget'], $r['actual'], $r['committed'], $r['variance']]));
        }

        return view('admin.finance.budget', ['b' => $b, 'v' => $v, 'through' => $through, 'accounts' => Account::query()->where(['organization_id' => $org, 'active' => true, 'postable' => true])->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name']),
            'centers' => CostCenter::query()->where(['organization_id' => $org, 'active' => true])->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function setLine(Request $request, string $budget)
    {
        $this->allow($request, 'finance.budget.manage');
        $b = $this->find(Budget::class, $budget);
        $d = $request->validate(['account_id' => ['required', 'integer'], 'cost_center_id' => ['nullable', 'integer'], 'period_number' => ['required', 'integer', 'between:0,12'], 'amount' => ['required', 'numeric']]);
        $this->budgets->setLine($b, (int) $d['account_id'], $d['cost_center_id'] ?: null, (int) $d['period_number'], (string) $d['amount']);

        return back()->with('success', __('Saved.'));
    }

    public function approve(Request $request, string $budget)
    {
        $this->allow($request, 'finance.budget.approve');
        $this->budgets->approve($this->find(Budget::class, $budget), $request->user());

        return back()->with('success', __('Budget approved.'));
    }

    public function check(Request $request)
    {
        $this->allow($request, 'finance.budget.view');
        $d = $request->validate(['account' => ['required', 'string', 'max:20'], 'amount' => ['required', 'numeric']]);
        $r = $this->budgets->checkPurchase($this->organizationId(), $d['account'], null, (string) $d['amount']);

        return back()->with($r['status'] === 'ok' ? 'success' : 'warning', $r['message']);
    }
}
