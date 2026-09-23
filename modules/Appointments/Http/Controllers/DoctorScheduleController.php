<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use App\Services\Appointments\ScheduleGenerationService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\StoreDoctorScheduleRequest;

class DoctorScheduleController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private ScheduleGenerationService $scheduleGenerator,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $schedules = DoctorSchedule::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($request->filled('day_of_week'), fn ($q, $day) => $q->where('day_of_week', $day))
            ->with(['doctor', 'provider', 'department', 'specialty', 'room'])
            ->latest()
            ->paginate(20);

        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->get();
        $departments = Department::where('company_id', $companyId)->get();
        $specialties = Specialty::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.schedules.create', compact('doctors', 'providers', 'departments', 'specialties'));
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $validated = $request->validated();
        $schedule = DoctorSchedule::create($validated);

        if ($validated['is_publish_slots'] ?? false) {
            $from = Carbon::parse($validated['slot_date'] ?? today());
            $to = $from->copy()->addDays(($validated['generate_days'] ?? 1) - 1);
            $this->scheduleGenerator->generateForRange($schedule, $from, $to);
        }

        $this->auditLogger->log('CREATE', DoctorSchedule::class, $schedule->id, null, $schedule->toArray(), $request);

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule created successfully.');
    }

    public function show(DoctorSchedule $schedule)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $schedule->load(['doctor', 'provider', 'department', 'specialty', 'room', 'slots' => fn ($q) => $q->orderBy('slot_datetime')]);

        return view('admin.schedules.show', compact('schedule'));
    }

    public function edit(DoctorSchedule $schedule)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->get();
        $departments = Department::where('company_id', $companyId)->get();
        $specialties = Specialty::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.schedules.edit', compact('schedule', 'doctors', 'providers', 'departments', 'specialties'));
    }

    public function update(Request $request, DoctorSchedule $schedule)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['integer', 'min:5', 'max:120'],
            'buffer_minutes' => ['integer', 'min:0', 'max:60'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'default_capacity_per_slot' => ['integer', 'min:1', 'max:50'],
            'overbooking_limit' => ['integer', 'min:0', 'max:20'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $schedule->toArray();
        $schedule->update($validated);

        $this->auditLogger->log('UPDATE', DoctorSchedule::class, $schedule->id, $oldValues, $schedule->toArray(), $request);

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule updated successfully.');
    }

    public function destroy(DoctorSchedule $schedule)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $oldValues = $schedule->toArray();
        $schedule->delete();

        $this->auditLogger->log('DELETE', DoctorSchedule::class, $schedule->id, $oldValues, null, request());

        return redirect()->route('admin.schedules.index')->with('success', 'Doctor schedule deleted successfully.');
    }

    public function generateSlots(Request $request, DoctorSchedule $schedule)
    {
        $this->authorize('manageSchedule', \App\Models\Appointment::class);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $created = $this->scheduleGenerator->generateForRange($schedule, Carbon::parse($validated['from']), Carbon::parse($validated['to']));

        $this->auditLogger->log('GENERATE_SLOTS', DoctorSchedule::class, $schedule->id, null, ['count' => $created->count()], $request);

        return redirect()->route('admin.schedules.show', $schedule)->with('success', "{$created->count()} slot(s) generated.");
    }
}
