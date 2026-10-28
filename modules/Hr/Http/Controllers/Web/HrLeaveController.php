<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveLedger;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrLeaveType;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\LeaveRequestService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Leave types, requests and the approval chain, balances, accrual and corrections. */
class HrLeaveController extends HrController
{
    public function __construct(TenantContextResolver $tenant, private readonly LeaveBalanceService $balances, private readonly LeaveRequestService $requests)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'leave.request.view');
        $org = $this->organizationId();
        $user = $request->user();
        $mine = HrEmployee::where(['organization_id' => $org, 'user_id' => $user->id])->first();
        $rows = HrLeaveRequest::query()->with(['employee', 'type'])->where('organization_id', $org)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v), fn ($q) => $q->orderByRaw("status in ('submitted','manager_approved') desc"))->latest('id')->paginate(30)->withQueryString();

        return view('admin.hr.leave', [
            'rows' => $rows, 'types' => HrLeaveType::where('organization_id', $org)->orderBy('code')->get(), 'employees' => $this->employeeChoices(), 'myEmployeeId' => $mine?->id, 'canHr' => $user->can('leave.approve.hr'), 'canManager' => $user->can('leave.approve.manager'),
        ]);
    }

    public function decide(Request $request, HrLeaveRequest $leave): RedirectResponse
    {
        $as = $request->input('as') === 'hr' ? 'hr' : 'manager';
        $this->scoped($request, $leave, $as === 'hr' ? 'leave.approve.hr' : 'leave.approve.manager');
        $approve = $request->input('decision') === 'approve';
        $as === 'hr' ? $this->requests->decideAsHr($leave, $request->user(), $approve, $request->input('note')) : $this->requests->decideAsManager($leave, $request->user(), $approve, $request->input('note'));

        return back()->with('success', __('Decision saved.'));
    }

    public function balances(Request $request)
    {
        $this->allow($request, 'leave.balance.view');
        $org = $this->organizationId();
        $types = HrLeaveType::where(['organization_id' => $org, 'status' => 'active'])->where('annual_days', '>', 0)->orderBy('code')->get();
        $employees = HrEmployee::where('organization_id', $org)->whereIn('status', ['active', 'on_leave'])->orderBy('first_name')->paginate(40);
        $sums = HrLeaveLedger::where('organization_id', $org)->whereIn('employee_id', $employees->pluck('id'))->selectRaw('employee_id, leave_type_id, SUM(days) d')->groupBy('employee_id', 'leave_type_id')->get()->groupBy('employee_id');

        return view('admin.hr.balances', ['employees' => $employees, 'types' => $types, 'sums' => $sums, 'allEmployees' => $this->employeeChoices()]);
    }

    public function types(Request $request)
    {
        $this->allow($request, 'leave.type.manage');

        return view('admin.hr.leave-types', ['types' => HrLeaveType::where('organization_id', $this->organizationId())->orderBy('code')->get()]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        $this->allow($request, 'leave.type.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:80'], 'is_paid' => ['nullable', 'boolean'], 'annual_days' => ['required', 'numeric', 'min:0', 'max:365'], 'accrual' => ['required', 'in:upfront,monthly,none'], 'carry_forward_max' => ['nullable', 'numeric', 'min:0'], 'max_consecutive_days' => ['nullable', 'integer', 'min:1'], 'document_after_days' => ['nullable', 'integer', 'min:1'], 'gender_restriction' => ['nullable', 'in:male,female'], 'requires_hr_approval' => ['nullable', 'boolean']]);
        HrLeaveType::updateOrCreate(['organization_id' => $this->organizationId(), 'code' => strtoupper($v['code'])], array_merge($v, ['code' => strtoupper($v['code']), 'is_paid' => $request->boolean('is_paid'), 'requires_hr_approval' => $request->boolean('requires_hr_approval')]));

        return back()->with('success', __('Leave type saved.'));
    }

    public function accrue(Request $request): RedirectResponse
    {
        $this->allow($request, 'leave.accrue');
        $v = $request->validate(['action' => ['required', 'in:open_year,accrue_month,roll_year'], 'year' => ['required', 'integer', 'min:2000', 'max:2100'], 'month' => ['nullable', 'integer', 'between:1,12']]);
        $org = $this->organizationId();
        $msg = match ($v['action']) {
            'open_year' => __(':n entitlement row(s) added.', ['n' => $this->balances->openYear($org, (int) $v['year'])]),
            'accrue_month' => __(':n accrual row(s) added.', ['n' => $this->balances->accrueMonth($org, CarbonImmutable::create((int) $v['year'], (int) ($v['month'] ?? 1), 15))]),
            default => (function () use ($org, $v) {
                $r = $this->balances->rollYear($org, (int) $v['year']);

                return __('Year rolled: :c carried forward, :l lapsed.', ['c' => $r['carried'], 'l' => $r['lapsed']]);
            })(),
        };

        return back()->with('success', $msg);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $this->allow($request, 'leave.balance.adjust');
        $v = $request->validate(['employee' => ['required', 'string'], 'leave_type_id' => ['required', 'integer'], 'days' => ['required', 'numeric', 'not_in:0'], 'reason' => ['required', 'string', 'max:200']]);
        $type = HrLeaveType::where('organization_id', $this->organizationId())->findOrFail($v['leave_type_id']);
        $this->balances->adjust($this->findEmployee($v['employee']), $type, (string) $v['days'], $v['reason'], $request->user());

        return back()->with('success', __('Balance corrected.'));
    }
}
