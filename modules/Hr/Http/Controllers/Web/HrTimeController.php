<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrDevice;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\Hr\HrPunch;
use App\Models\Hr\HrRegularization;
use App\Models\Hr\HrRosterEntry;
use App\Models\Hr\HrShift;
use App\Models\Hr\HrStaffingRequirement;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\OvertimeService;
use App\Services\Hr\PunchIngestService;
use App\Services\Hr\RosterService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Shifts and the duty roster, attendance, punches and devices, corrections and overtime. */
class HrTimeController extends HrController
{
    public function __construct(TenantContextResolver $tenant, private readonly RosterService $roster, private readonly AttendanceService $attendance, private readonly PunchIngestService $punches, private readonly OvertimeService $overtime)
    {
        parent::__construct($tenant);
    }

    public function shifts(Request $request)
    {
        $this->allow($request, 'hr.roster.view');
        $org = $this->organizationId();

        return view('admin.hr.shifts', ['shifts' => HrShift::where('organization_id', $org)->orderBy('start_time')->get(), 'requirements' => HrStaffingRequirement::with('shift')->where('organization_id', $org)->get(), 'departments' => $this->departmentChoices(), 'jobs' => $this->jobChoices()]);
    }

    public function storeShift(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.shift.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:80'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i'], 'break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'], 'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120']]);
        $this->roster->createShift($this->organizationId(), $v['code'], $v['name'], $v['start_time'], $v['end_time'], (int) ($v['break_minutes'] ?? 0), (int) ($v['grace_minutes'] ?? 10));

        return back()->with('success', __('Shift added.'));
    }

    public function storeRequirement(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.shift.manage');
        $v = $request->validate(['department_id' => ['required', 'integer'], 'shift_id' => ['required', 'integer'], 'job_id' => ['nullable', 'integer'], 'min_count' => ['required', 'integer', 'min:1', 'max:200']]);
        $shift = HrShift::where('organization_id', $this->organizationId())->findOrFail($v['shift_id']);
        $this->roster->require($this->organizationId(), $this->departmentChoices()->firstOrFail(fn ($d) => $d->id === (int) $v['department_id'])->id, $shift, (int) $v['min_count'], $v['job_id'] ?? null);

        return back()->with('success', __('Staffing need saved.'));
    }

    public function roster(Request $request)
    {
        $this->allow($request, 'hr.roster.view');
        $org = $this->organizationId();
        $from = CarbonImmutable::parse($request->query('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $to = $from->addDays(6);
        $dept = $request->query('department');
        $employees = HrEmployee::query()->where('organization_id', $org)->whereIn('status', ['active', 'on_leave'])->when($dept, fn ($q, $v) => $q->where('department_id', $v))->orderBy('first_name')->get();
        $entries = HrRosterEntry::with('shift')->where('organization_id', $org)->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->whereIn('employee_id', $employees->pluck('id'))->get()->groupBy(fn ($e) => $e->employee_id.'|'.$e->work_date->toDateString());

        return view('admin.hr.roster', [
            'from' => $from, 'days' => collect(range(0, 6))->map(fn ($i) => $from->addDays($i)), 'employees' => $employees, 'entries' => $entries, 'shifts' => HrShift::where(['organization_id' => $org, 'status' => 'active'])->orderBy('start_time')->get(),
            'departments' => $this->departmentChoices(), 'department' => $dept, 'shortfalls' => $this->roster->shortfalls($org, $from, $to), 'drafts' => HrRosterEntry::where(['organization_id' => $org, 'status' => 'draft'])->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->count(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.roster.manage');
        $v = $request->validate(['employee' => ['required', 'string'], 'work_date' => ['required', 'date'], 'shift_id' => ['required', 'integer']]);
        $shift = HrShift::where('organization_id', $this->organizationId())->findOrFail($v['shift_id']);
        $this->roster->assign($this->findEmployee($v['employee']), CarbonImmutable::parse($v['work_date']), $shift, $request->user());

        return back()->with('success', __('Duty added.'));
    }

    public function unassign(Request $request, HrRosterEntry $entry): RedirectResponse
    {
        $this->scoped($request, $entry, 'hr.roster.manage');
        $this->roster->remove($entry);

        return back()->with('success', __('Duty removed.'));
    }

    public function publish(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.roster.manage');
        $v = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date'], 'department' => ['nullable', 'integer']]);
        $n = $this->roster->publish($this->organizationId(), CarbonImmutable::parse($v['from']), CarbonImmutable::parse($v['to']), $v['department'] ?? null);

        return back()->with('success', __(':n duty(ies) published.', ['n' => $n]));
    }

    public function attendance(Request $request)
    {
        $this->allow($request, 'attendance.view');
        $org = $this->organizationId();
        $date = CarbonImmutable::parse($request->query('date', now()->toDateString()));
        $rows = HrAttendanceDay::query()->with(['employee:id,employee_no,first_name,last_name,department_id', 'shift:id,code'])->where('organization_id', $org)->whereDate('work_date', $date->toDateString())
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('department'), fn ($q, $v) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $v)))->orderBy('employee_id')->paginate(50)->withQueryString();

        return view('admin.hr.attendance', [
            'rows' => $rows, 'date' => $date, 'departments' => $this->departmentChoices(), 'counts' => HrAttendanceDay::where('organization_id', $org)->whereDate('work_date', $date->toDateString())->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'devices' => HrDevice::where('organization_id', $org)->get(), 'unmapped' => HrPunch::where('organization_id', $org)->whereNull('employee_id')->selectRaw('device_code, count(*) c, max(punched_at) last_at')->groupBy('device_code')->get(), 'employees' => $this->employeeChoices(),
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $this->allow($request, 'attendance.process');
        $v = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        $n = $this->attendance->processRange($this->organizationId(), CarbonImmutable::parse($v['from']), CarbonImmutable::parse($v['to']));

        return back()->with('success', __(':n day record(s) worked out.', ['n' => $n]));
    }

    public function importPunches(Request $request): RedirectResponse
    {
        $this->allow($request, 'attendance.punch.import');
        $v = $request->validate(['device' => ['nullable', 'string'], 'punches' => ['required', 'string', 'max:200000']]);
        $device = ! empty($v['device']) ? HrDevice::where('organization_id', $this->organizationId())->where('code', $v['device'])->firstOrFail() : null;
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($v['punches'])) ?: [] as $line) {
            [$code, $at] = array_pad(array_map('trim', explode(',', $line, 2)), 2, '');
            if ($code === '' || strtotime($at) === false) {
                continue;
            }
            $rows[] = ['code' => $code, 'at' => $at];
        }
        $r = $this->punches->ingest($this->organizationId(), $device, $rows, 'import');

        return back()->with('success', __(':s stored, :d already known, :u not yet matched to a person.', ['s' => $r['stored'], 'd' => $r['duplicates'], 'u' => $r['unresolved']]));
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $this->allow($request, 'attendance.device.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:100'], 'kind' => ['required', 'in:fingerprint,face,card,mobile,manual']]);
        HrDevice::create($v + ['organization_id' => $this->organizationId(), 'branch_id' => $this->tenant->getBranchId()]);

        return back()->with('success', __('Device added.'));
    }

    public function mapCode(Request $request): RedirectResponse
    {
        $this->allow($request, 'attendance.punch.import');
        $v = $request->validate(['code' => ['required', 'string', 'max:40'], 'employee' => ['required', 'string']]);
        $n = $this->punches->mapCode($this->organizationId(), $v['code'], $this->findEmployee($v['employee']));

        return back()->with('success', __(':n punch(es) matched.', ['n' => $n]));
    }

    public function corrections(Request $request)
    {
        $this->allow($request, 'attendance.regularize.approve');
        $rows = HrRegularization::with('employee')->where('organization_id', $this->organizationId())->orderByRaw("status = 'requested' desc")->latest('id')->paginate(30);

        return view('admin.hr.corrections', ['rows' => $rows]);
    }

    public function decideCorrection(Request $request, HrRegularization $regularization): RedirectResponse
    {
        $this->scoped($request, $regularization, 'attendance.regularize.approve');
        $this->attendance->decideRegularization($regularization, $request->user(), $request->input('decision') === 'approve', $request->input('note'));

        return back()->with('success', __('Decision saved.'));
    }

    public function overtime(Request $request)
    {
        $this->allow($request, 'payroll.overtime.approve');
        $rows = HrOvertimeEntry::with('employee')->where('organization_id', $this->organizationId())->orderByRaw("status = 'pending' desc")->orderByDesc('work_date')->paginate(40);

        return view('admin.hr.overtime', ['rows' => $rows]);
    }

    public function syncOvertime(Request $request): RedirectResponse
    {
        $this->allow($request, 'payroll.overtime.approve');
        $v = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date']]);
        $n = $this->overtime->syncFromAttendance($this->organizationId(), CarbonImmutable::parse($v['from']), CarbonImmutable::parse($v['to']));

        return back()->with('success', __(':n new claim(s) found in attendance.', ['n' => $n]));
    }

    public function decideOvertime(Request $request, HrOvertimeEntry $entry): RedirectResponse
    {
        $this->scoped($request, $entry, 'payroll.overtime.approve');
        $request->input('decision') === 'reject' ? $this->overtime->reject($entry, $request->user()) : $this->overtime->approve($entry, $request->user(), $request->filled('minutes') ? (int) $request->input('minutes') : null);

        return back()->with('success', __('Decision saved.'));
    }
}
