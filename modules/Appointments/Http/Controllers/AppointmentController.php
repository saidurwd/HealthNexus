<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\AppointmentDocument;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentLifecycleService;
use App\Services\AuditLogger;
use App\Services\FileService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Appointments\Http\Requests\StoreAppointmentRequest;
use Modules\Appointments\Http\Requests\UpdateAppointmentRequest;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentBookingService $bookingService,
        private AppointmentLifecycleService $lifecycle,
        private AuditLogger $auditLogger,
        private FileService $fileService,
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
            ->when($request->filled('provider_id'), fn ($q, $id) => $q->where('provider_id', $id))
            ->when($request->filled('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($request->filled('date'), fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->when($request->filled('date_from'), fn ($q, $date) => $q->whereDate('appointment_date', '>=', $date))
            ->when($request->filled('date_to'), fn ($q, $date) => $q->whereDate('appointment_date', '<=', $date))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('source'), fn ($q, $source) => $q->where('source', $source))
            ->when($request->filled('appointment_type_id'), fn ($q, $id) => $q->where('appointment_type_id', $id))
            ->when($request->filled('search'), function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('appointment_no', 'like', "%{$search}%")
                        ->orWhereHas('patient', fn ($q3) => $q3->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('enterprise_patient_no', 'like', "%{$search}%"));
                });
            })
            ->with(['patient', 'doctor', 'provider', 'token', 'appointmentType'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.appointments.index', compact('appointments'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $patients = Patient::where('company_id', $companyId)->orderBy('first_name')->paginate(50);
        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = auth()->user()->branches()->where('branches.company_id', $companyId)->get();
        $appointmentTypes = AppointmentType::forCompany($companyId)->where('is_active', true)->orderBy('sort_order')->get();

        // Supports "Book Appointment" from the Patient 360 profile (spec §25) — the patient
        // comes pre-selected but the user can still change it via an explicit action.
        $selectedPatient = $request->filled('patient_id')
            ? Patient::where('company_id', $companyId)->find($request->input('patient_id'))
            : null;

        return view('admin.appointments.create', compact('patients', 'doctors', 'providers', 'branches', 'appointmentTypes', 'selectedPatient'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validated();
        $allowOverbooking = $request->boolean('allow_overbooking') && $request->user()->can('appointments.override');

        $appointment = $this->bookingService->book($validated, $request->user(), $allowOverbooking);

        $this->auditLogger->log('CREATE', Appointment::class, $appointment->id, null, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient', 'doctor', 'provider', 'slot', 'token', 'appointmentType', 'room', 'documents', 'vitalSigns', 'diagnoses', 'prescriptions', 'investigationOrders']);

        return view('admin.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $patients = Patient::where('company_id', $companyId)->orderBy('first_name')->paginate(50);
        $doctors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['doctor', 'consultant']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = auth()->user()->branches()->where('branches.company_id', $companyId)->get();

        return view('admin.appointments.edit', compact('appointment', 'patients', 'doctors', 'providers', 'branches'));
    }

    /**
     * Deliberately does not allow "status" here — status only changes through the dedicated
     * confirm/check-in/cancel/no-show/reschedule actions, each enforced by
     * AppointmentStateMachine. Free-form status writes through a generic update endpoint were
     * the exact gap that let status be set to any value with no transition validation.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validated();
        $oldValues = $appointment->toArray();

        $appointment->update($validated);

        $this->auditLogger->log('UPDATE', Appointment::class, $appointment->id, $oldValues, $appointment->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        abort_if($appointment->status === 'completed', 422, 'A completed appointment cannot be deleted — it is part of the clinical record.');

        $oldValues = $appointment->toArray();
        $appointment->delete();

        $this->auditLogger->log('DELETE', Appointment::class, $appointment->id, $oldValues, null, request());

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment deleted successfully.');
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorize('reschedule', $appointment);

        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->lifecycle->reschedule($appointment, $validated, $request->user());

        $this->auditLogger->log('RESCHEDULE', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Appointment rescheduled successfully.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->lifecycle->cancel($appointment, $validated['cancellation_reason'], $request->user());

        $this->auditLogger->log('CANCEL', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment cancelled successfully.');
    }

    public function noShow(Request $request, Appointment $appointment)
    {
        $this->authorize('markNoShow', $appointment);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldValues = $appointment->toArray();
        $this->lifecycle->markNoShow($appointment, $validated['reason'] ?? null, $request->user());

        $this->auditLogger->log('NO_SHOW', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment marked as no-show.');
    }

    public function confirm(Request $request, Appointment $appointment)
    {
        $this->authorize('confirm', $appointment);

        $oldValues = $appointment->toArray();
        $this->lifecycle->confirm($appointment, $request->user());

        $this->auditLogger->log('CONFIRM', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Appointment confirmed successfully.');
    }

    public function checkIn(Request $request, Appointment $appointment)
    {
        $this->authorize('checkIn', $appointment);

        $oldValues = $appointment->toArray();
        $this->lifecycle->checkIn($appointment, $request->user());

        $this->auditLogger->log('CHECK_IN', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Patient checked in successfully.');
    }

    public function start(Request $request, Appointment $appointment)
    {
        $this->authorize('checkIn', $appointment);

        $oldValues = $appointment->toArray();
        $this->lifecycle->startConsultation($appointment, $request->user());

        $this->auditLogger->log('START', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Consultation started.');
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $oldValues = $appointment->toArray();
        $this->lifecycle->complete($appointment, $request->user());

        $this->auditLogger->log('COMPLETE', Appointment::class, $appointment->id, $oldValues, $appointment->fresh()->toArray(), $request);

        return redirect()->route('admin.appointments.index')->with('success', 'Appointment completed successfully.');
    }

    public function createFollowUp(Request $request, Appointment $appointment)
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validate([
            'appointment_date' => ['nullable', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string'],
        ]);

        $followUp = $this->bookingService->bookFollowUp($appointment, $validated, $request->user());

        $this->auditLogger->log('CREATE', Appointment::class, $followUp->id, null, $followUp->toArray(), $request);

        return redirect()->route('admin.appointments.show', $followUp)->with('success', 'Follow-up appointment created.');
    }

    public function addNote(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $this->lifecycle->addNote($appointment, $validated['note'], $request->user());

        $this->auditLogger->log('NOTE_ADDED', Appointment::class, $appointment->id, null, ['note' => $validated['note']], $request);

        return back()->with('success', 'Note added successfully.');
    }

    public function history(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $history = $appointment->statusHistory()->with('changer')->latest()->get();

        return view('admin.appointments.history', compact('appointment', 'history'));
    }

    public function print(Appointment $appointment)
    {
        $this->authorize('print', $appointment);

        $appointment->load(['patient', 'doctor', 'provider', 'appointmentType']);

        return view('admin.appointments.print', compact('appointment'));
    }

    /**
     * Reuses the same private-disk File infrastructure Patients uses for its documents (spec
     * §39: "Reuse Phase 1/Phase 0 file management"). Covers referral letters, booking
     * confirmations, and other administrative — never clinical — attachments.
     */
    public function uploadDocument(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'document_type' => ['required', 'string', 'max:100'],
        ]);

        $file = $this->fileService->store($validated['document'], $appointment, $request->user());

        $document = AppointmentDocument::create([
            'company_id' => $appointment->company_id,
            'appointment_id' => $appointment->id,
            'file_id' => $file->id,
            'document_type' => $validated['document_type'],
            'uploaded_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('UPLOAD', AppointmentDocument::class, $document->id, null, $document->toArray(), $request);

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Document uploaded successfully.');
    }

    public function downloadDocument(Request $request, Appointment $appointment, AppointmentDocument $document)
    {
        $this->authorize('view', $appointment);

        abort_unless($document->appointment_id === $appointment->id, 404);

        $this->auditLogger->log('DOWNLOAD', AppointmentDocument::class, $document->id, null, null, $request);

        return Storage::disk($document->file->disk)->download($document->file->path, $document->file->original_name);
    }

    public function deleteDocument(Appointment $appointment, AppointmentDocument $document)
    {
        $this->authorize('update', $appointment);

        abort_unless($document->appointment_id === $appointment->id, 404);

        $oldValues = $document->toArray();
        Storage::disk($document->file->disk)->delete($document->file->path);
        $document->file->delete();
        $document->delete();

        $this->auditLogger->log('DELETE', AppointmentDocument::class, $document->id, $oldValues, null, request());

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Document removed successfully.');
    }
}
