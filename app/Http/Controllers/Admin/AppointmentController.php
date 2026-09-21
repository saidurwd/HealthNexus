<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use App\Models\Branch;
use App\Services\Appointments\AppointmentService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService,
        private AuditLogger $auditLogger
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $appointments = Appointment::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('patient_id'), fn ($q, $id) => $q->where('patient_id', $id))
            ->when($request->filled('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($request->filled('date'), fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->with(['patient', 'doctor', 'token'])
            ->latest()
            ->paginate(20);

        return view('admin.appointments.index', compact('appointments'));
    }

    public function create()
    {
        $this->authorize('create', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $patients = Patient::where('company_id', $companyId)->get();
        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $branches = auth()->user()->branches()->where('companies.id', $companyId)->get();

        return view('admin.appointments.create', compact('patients', 'doctors', 'branches'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validated();
        $appointment = $this->appointmentService->createAppointment($validated, $request->user());

        $this->auditLogger->log('CREATE', Appointment::class, $appointment->id, null, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient', 'doctor', 'slot', 'token', 'vitalSigns', 'diagnoses', 'prescriptions', 'investigationOrders']);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $patients = Patient::where('company_id', $companyId)->get();
        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $branches = auth()->user()->branches()->where('companies.id', $companyId)->get();

        return view('admin.appointments.edit', compact('appointment', 'patients', 'doctors', 'branches'));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validated();
        $oldValues = $appointment->toArray();

        $this->appointmentService->updateAppointment($appointment, $validated);

        $this->auditLogger->log('UPDATE', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $oldValues = $appointment->toArray();
        $appointment->delete();

        $this->auditLogger->log('DELETE', Appointment::class, $appointment->id, $oldValues, null, request());

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment deleted successfully.');
    }
}
