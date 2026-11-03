<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrAppraisal;
use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveLedger;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrLeaveType;
use App\Models\Hr\HrRegularization;
use App\Models\Hr\HrRosterEntry;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\LeaveRequestService;
use App\Services\Hr\PayslipService;
use App\Services\Hr\PerformanceService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Employee and manager self-service (S11): everything here is about the person whose login this is — never somebody else's record. */
class HrSelfController extends HrController
{
    public function __construct(TenantContextResolver $tenant, private readonly PayslipService $payslips, private readonly LeaveRequestService $leave, private readonly AttendanceService $attendance, private readonly PerformanceService $performance)
    {
        parent::__construct($tenant);
    }

    public function home(Request $request)
    {
        $this->allow($request, 'hr.self.view');
        $me = $this->me($request);
        $types = HrLeaveType::where(['organization_id' => $me->organization_id, 'status' => 'active'])->where('annual_days', '>', 0)->orderBy('code')->get();
        $sums = HrLeaveLedger::where('employee_id', $me->id)->selectRaw('leave_type_id, SUM(days) d')->groupBy('leave_type_id')->pluck('d', 'leave_type_id');

        return view('admin.hr.self.home', [
            'me' => $me->load(['department', 'job', 'manager']), 'types' => $types, 'sums' => $sums, 'requests' => HrLeaveRequest::with('type')->where('employee_id', $me->id)->latest('id')->limit(10)->get(),
            'roster' => HrRosterEntry::with('shift')->where('employee_id', $me->id)->whereDate('work_date', '>=', now()->toDateString())->whereDate('work_date', '<=', now()->addDays(7)->toDateString())->orderBy('work_date')->get(),
            'attendance' => HrAttendanceDay::where('employee_id', $me->id)->whereDate('work_date', '>=', now()->subDays(14)->toDateString())->orderByDesc('work_date')->get(), 'corrections' => HrRegularization::where('employee_id', $me->id)->latest('id')->limit(5)->get(),
            'payslips' => $this->payslips->forEmployee($me)->take(12), 'appraisals' => HrAppraisal::with('cycle')->where('employee_id', $me->id)->whereIn('status', ['submitted', 'acknowledged'])->latest('id')->limit(3)->get(),
            'team' => HrEmployee::where('manager_id', $me->id)->count(),
        ]);
    }

    public function requestLeave(Request $request): RedirectResponse
    {
        $this->allow($request, 'leave.request.create');
        $me = $this->me($request);
        $v = $request->validate(['leave_type_id' => ['required', 'integer'], 'from_date' => ['required', 'date'], 'to_date' => ['required', 'date', 'after_or_equal:from_date'], 'half_day' => ['nullable', 'boolean'], 'reason' => ['nullable', 'string', 'max:300']]);
        $type = HrLeaveType::where('organization_id', $me->organization_id)->findOrFail($v['leave_type_id']);
        $this->leave->request($me, $type, CarbonImmutable::parse($v['from_date']), CarbonImmutable::parse($v['to_date']), $request->boolean('half_day'), $v['reason'] ?? null);

        return back()->with('success', __('Leave requested. Your manager will decide.'));
    }

    public function cancelLeave(Request $request, HrLeaveRequest $leave): RedirectResponse
    {
        $this->allow($request, 'hr.self.view');
        abort_unless($leave->employee_id === $this->me($request)->id, 404);
        $this->leave->cancel($leave, $request->user());

        return back()->with('success', __('Leave cancelled.'));
    }

    public function requestCorrection(Request $request): RedirectResponse
    {
        $this->allow($request, 'attendance.regularize.request');
        $me = $this->me($request);
        $v = $request->validate(['work_date' => ['required', 'date', 'before_or_equal:today'], 'in' => ['nullable', 'date_format:H:i'], 'out' => ['nullable', 'date_format:H:i'], 'reason' => ['required', 'string', 'max:300']]);
        $date = CarbonImmutable::parse($v['work_date']);
        $this->attendance->requestRegularization($me, $date, ! empty($v['in']) ? $date->setTimeFromTimeString($v['in']) : null, ! empty($v['out']) ? $date->setTimeFromTimeString($v['out']) : null, $v['reason']);

        return back()->with('success', __('Correction sent to your manager.'));
    }

    public function payslip(Request $request, int $line)
    {
        $this->allow($request, 'hr.self.view');
        $slip = $this->payslips->find($this->me($request), $line);
        abort_if($slip === null, 404);

        return view('admin.hr.payslip', ['line' => $slip->load(['run.period', 'employee', 'items']), 'back' => route('admin.hr.me')]);
    }

    public function acknowledge(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $this->allow($request, 'hr.self.view');
        $this->performance->acknowledge($appraisal, $this->me($request));

        return back()->with('success', __('Acknowledged.'));
    }

    /** A manager's own team: leave and corrections waiting for them. */
    public function team(Request $request)
    {
        $this->allow($request, 'hr.self.view');
        $me = $this->me($request);
        $ids = HrEmployee::where('manager_id', $me->id)->pluck('id');
        abort_if($ids->isEmpty(), 403, __('You do not manage anyone.'));

        return view('admin.hr.self.team', [
            'members' => HrEmployee::whereIn('id', $ids)->orderBy('first_name')->get(), 'leave' => HrLeaveRequest::with(['employee', 'type'])->whereIn('employee_id', $ids)->whereIn('status', ['submitted', 'manager_approved'])->latest('id')->get(),
            'corrections' => HrRegularization::with('employee')->whereIn('employee_id', $ids)->where('status', 'requested')->latest('id')->get(), 'today' => HrAttendanceDay::with('employee')->whereIn('employee_id', $ids)->whereDate('work_date', now()->toDateString())->get(),
            'canLeave' => $request->user()->can('leave.approve.manager'), 'canCorrections' => $request->user()->can('attendance.regularize.approve'),
        ]);
    }
}
