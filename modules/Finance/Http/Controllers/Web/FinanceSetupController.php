<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\AccountRole;
use App\Models\Finance\CostCenter;
use App\Models\Finance\FiscalYear;
use App\Models\Finance\Period;
use App\Services\Finance\ChartService;
use App\Services\Finance\FinancePolicy;
use App\Services\Finance\FiscalService;
use App\Services\Finance\YearEndService;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** The chart of accounts, cost centres, account roles, fiscal years and periods, and the finance settings. */
class FinanceSetupController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly ChartService $chart, private readonly FiscalService $fiscal, private readonly YearEndService $yearEnd, private readonly SettingsService $settings)
    {
        parent::__construct($tenant);
    }

    public function accounts(Request $request)
    {
        $this->allow($request, 'finance.chart.view');
        $accounts = Account::query()->where('organization_id', $this->organizationId())->orderBy('code')->get();

        return view('admin.finance.accounts', ['accounts' => $accounts, 'types' => ChartService::TYPES, 'controls' => ChartService::CONTROLS]);
    }

    public function startBooks(Request $request)
    {
        $this->allow($request, 'finance.chart.manage');
        $made = $this->chart->seedStarter($this->organizationId());

        return back()->with('success', __('Starter chart ready: :a accounts and :r account roles added.', ['a' => $made['accounts'], 'r' => $made['roles']]));
    }

    public function storeAccount(Request $request)
    {
        $this->allow($request, 'finance.chart.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', 'in:'.implode(',', ChartService::TYPES)], 'parent_id' => ['nullable', 'integer'], 'postable' => ['nullable', 'boolean'], 'control' => ['nullable', 'in:'.implode(',', ChartService::CONTROLS)], 'note' => ['nullable', 'string', 'max:200']]);
        $this->chart->createAccount($this->organizationId(), $d + ['postable' => $request->boolean('postable', true)]);

        return back()->with('success', __('Account added.'));
    }

    public function updateAccount(Request $request, Account $account)
    {
        $this->allow($request, 'finance.chart.manage');
        abort_unless($account->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['name' => ['required', 'string', 'max:150'], 'active' => ['nullable', 'boolean'], 'note' => ['nullable', 'string', 'max:200']]);
        $this->chart->updateAccount($account, ['name' => $d['name'], 'active' => $request->boolean('active'), 'note' => $d['note'] ?? null]);

        return back()->with('success', __('Saved.'));
    }

    public function deleteAccount(Request $request, Account $account)
    {
        $this->allow($request, 'finance.chart.manage');
        abort_unless($account->organization_id === $this->organizationId(), 404);
        $this->chart->deleteAccount($account);

        return back()->with('success', __('Account removed.'));
    }

    public function costCenters(Request $request)
    {
        $this->allow($request, 'finance.chart.view');

        return view('admin.finance.cost-centers', ['rows' => CostCenter::query()->where('organization_id', $this->organizationId())->orderBy('code')->get()]);
    }

    public function storeCostCenter(Request $request)
    {
        $this->allow($request, 'finance.chart.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:120'], 'kind' => ['required', 'in:cost,profit']]);
        $this->chart->createCostCenter($this->organizationId(), $d['code'], $d['name'], $d['kind']);

        return back()->with('success', __('Cost centre added.'));
    }

    public function roles(Request $request)
    {
        $this->allow($request, 'finance.chart.view');
        $org = $this->organizationId();

        return view('admin.finance.roles', ['roles' => AccountRole::query()->where('organization_id', $org)->with('account:id,code,name')->orderBy('role')->orderBy('scope_key')->get(),
            'accounts' => Account::query()->where(['organization_id' => $org, 'active' => true, 'postable' => true])->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function saveRole(Request $request)
    {
        $this->allow($request, 'finance.chart.manage');
        $d = $request->validate(['role' => ['required', 'string', 'max:40'], 'scope_key' => ['required', 'string', 'max:60'], 'account_id' => ['required', 'integer']]);
        $this->chart->setRole($this->organizationId(), $d['role'], $d['scope_key'], (int) $d['account_id']);

        return back()->with('success', __('Saved.'));
    }

    public function years(Request $request)
    {
        $this->allow($request, 'finance.chart.view');
        $org = $this->organizationId();
        $years = FiscalYear::query()->where('organization_id', $org)->with('periods')->orderByDesc('starts_on')->get();

        return view('admin.finance.years', ['years' => $years, 'blockers' => $years->mapWithKeys(fn ($y) => [$y->id => $y->status === 'open' ? $this->yearEnd->blockers($y) : []])]);
    }

    public function storeYear(Request $request)
    {
        $this->allow($request, 'finance.period.manage');
        $d = $request->validate(['start_year' => ['required', 'integer', 'between:2000,2100'], 'first_month' => ['required', 'integer', 'between:1,12']]);
        $this->fiscal->createYear($this->organizationId(), (int) $d['start_year'], (int) $d['first_month']);

        return back()->with('success', __('Fiscal year created with twelve periods.'));
    }

    public function lockPeriod(Request $request, Period $period)
    {
        $this->allow($request, 'finance.period.manage');
        abort_unless($period->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['action' => ['required', 'in:lock,unlock'], 'note' => ['nullable', 'string', 'max:200']]);
        $d['action'] === 'lock' ? $this->fiscal->lock($period, $request->user(), $d['note'] ?? null) : $this->fiscal->unlock($period, $request->user(), (string) ($d['note'] ?? ''));

        return back()->with('success', $d['action'] === 'lock' ? __('Period locked.') : __('Period opened again.'));
    }

    public function closeYear(Request $request, FiscalYear $year)
    {
        $this->allow($request, 'finance.year.close');
        abort_unless($year->organization_id === $this->organizationId(), 404);
        try {
            $this->yearEnd->close($year, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('The year is closed.'));
    }

    public function settings(Request $request)
    {
        $this->allow($request, 'finance.settings.manage');
        $p = app(FinancePolicy::class);

        return view('admin.finance.settings', ['v' => [
            'maker_checker' => $p->bool('journal.maker_checker', true), 'self_approval' => $p->bool('journal.allow_self_approval', false), 'locked_posting' => $p->bool('period.post_into_locked', false), 'first_month' => $p->int('period.first_month', 1),
            'writeoff_second' => $p->bool('receivable.writeoff_second_approver', true), 'payment_second' => $p->bool('payable.payment_second_approver', true), 'budget_mode' => (string) $p->get('budget.mode', 'warn'), 'catch_up' => $p->int('posting.catch_up_days', 45), 'asset_life' => $p->int('asset.default_life_months', 60),
        ]]);
    }

    public function saveSettings(Request $request)
    {
        $this->allow($request, 'finance.settings.manage');
        $d = $request->validate(['first_month' => ['required', 'integer', 'between:1,12'], 'budget_mode' => ['required', 'in:off,warn,block'], 'catch_up' => ['required', 'integer', 'between:1,365'], 'asset_life' => ['required', 'integer', 'between:1,600']]);
        foreach (['maker_checker' => 'journal.maker_checker', 'self_approval' => 'journal.allow_self_approval', 'locked_posting' => 'period.post_into_locked', 'writeoff_second' => 'receivable.writeoff_second_approver', 'payment_second' => 'payable.payment_second_approver'] as $field => $key) {
            $this->settings->set("finance.$key", $request->boolean($field), 'finance');
        }
        $this->settings->set('finance.period.first_month', (int) $d['first_month'], 'finance');
        $this->settings->set('finance.budget.mode', $d['budget_mode'], 'finance');
        $this->settings->set('finance.posting.catch_up_days', (int) $d['catch_up'], 'finance');
        $this->settings->set('finance.asset.default_life_months', (int) $d['asset_life'], 'finance');

        return back()->with('success', __('Saved.'));
    }
}
