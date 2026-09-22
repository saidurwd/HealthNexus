<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\Appointments\AppointmentService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\StoreAppointmentRequest;
use Modules\Appointments\Http\Requests\UpdateAppointmentRequest;

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

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->appointmentService->reschedule($appointment, $validated, $request->user());

        $this->auditLogger->log('RESCHEDULE', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Appointment rescheduled successfully.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->appointmentService->cancel($appointment, $validated['cancellation_reason'], $request->user());

        $this->auditLogger->log('CANCEL', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment cancelled successfully.');
    }

    public function noShow(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->appointmentService->markNoShow($appointment, $validated['reason'], $request->user());

        $this->auditLogger->log('NO_SHOW', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment marked as no-show.');
    }

    public function confirm(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $oldValues = $appointment->toArray();
        $this->appointmentService->confirm($appointment, $request->user());

        $this->auditLogger->log('CONFIRM', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Appointment confirmed successfully.');
    }

    public function checkIn(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $oldValues = $appointment->toArray();
        $this->appointmentService->checkIn($appointment, $request->user());

        $this->auditLogger->log('CHECK_IN', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Patient checked in successfully.');
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $oldValues = $appointment->toArray();
        $this->appointmentService->complete($appointment, $request->user());

        $this->auditLogger->log('COMPLETE', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment completed successfully.');
    }

    public function addNote(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $this->appointmentService->addNote($appointment, $validated['note'], $request->user());

        $this->auditLogger->log('NOTE_ADDED', Appointment::class, $appointment->id, null, ['note' => $validated['note']], $request);

        return back()->with('success', 'Note added successfully.');
    }

    public function history(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $history = $appointment->statusHistory()->with('changer')->latest()->get();

        return view('admin.appointments.history', compact('appointment', 'history'));
    }
}
