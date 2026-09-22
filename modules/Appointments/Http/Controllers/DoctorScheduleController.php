<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\Appointments\AppointmentService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\StoreDoctorScheduleRequest;

class DoctorScheduleController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AppointmentService $appointmentService
    ) {}

    public function index(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $schedules = DoctorSchedule::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($request->filled('day_of_week'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->with(['doctor', 'department'])
            ->latest()
            ->paginate(20);

        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $departments = Department::where('company_id', $companyId)->get();

        return view('admin.schedules.create', compact('doctors', 'departments'));
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $schedule = DoctorSchedule::create($validated);

        if ($validated['is_publish_slots'] ?? false) {
            $this->appointmentService->createSlots($schedule, $validated['slot_date'] ?? today());
        }

        $this->auditLogger->log('CREATE', DoctorSchedule::class, $schedule->id, null, $schedule->toArray(), $request);

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule created successfully.');
    }

    public function show(DoctorSchedule $schedule)
    {
        $schedule->load(['doctor', 'department', 'slots']);

        return view('admin.schedules.show', compact('schedule'));
    }

    public function edit(DoctorSchedule $schedule)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $departments = Department::where('company_id', $companyId)->get();

        return view('admin.schedules.edit', compact('schedule', 'doctors', 'departments'));
    }

    public function update(Request $request, DoctorSchedule $schedule)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['integer', 'min:5', 'max:120'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $schedule->toArray();
        $schedule->update($validated);

        $this->auditLogger->log('UPDATE', DoctorSchedule::class, $schedule->id, $oldValues, $schedule->toArray(), $request);

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule updated successfully.');
    }

    public function destroy(DoctorSchedule $schedule)
    {
        $oldValues = $schedule->toArray();
        $schedule->delete();

        $this->auditLogger->log('DELETE', DoctorSchedule::class, $schedule->id, $oldValues, null, request());

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule deleted successfully.');
    }
}
