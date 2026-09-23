<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\InvestigationOrder;
use App\Models\Prescription;
use App\Models\VitalSign;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentLifecycleService;
use App\Services\Appointments\OpdService;
use App\Services\AuditLogger;
use App\Services\EncounterLifecycleService;
use App\Services\EncounterService;
use Illuminate\Http\Request;
use Modules\Appointments\Http\Requests\StoreDiagnosisRequest;
use Modules\Appointments\Http\Requests\StoreInvestigationOrderRequest;
use Modules\Appointments\Http\Requests\StorePrescriptionRequest;
use Modules\Appointments\Http\Requests\StoreVitalSignRequest;

/**
 * The real, end-to-end OPD consultation workflow. It now creates/drives a real Encounter
 * (via EncounterService::findOrCreateFromAppointment()) instead of operating on the Appointment
 * alone — previously this whole subsystem never touched the Encounter model at all, which
 * directly violated the "Appointment ≠ Encounter" architectural rule and made a walk-in (no
 * appointment) visit structurally unrepresentable.
 */
class OpdConsultationController extends Controller
{
    public function __construct(
        private OpdService $opdService,
        private AppointmentLifecycleService $lifecycle,
        private AppointmentBookingService $bookingService,
        private EncounterService $encounterService,
        private EncounterLifecycleService $encounterLifecycle,
        private AuditLogger $auditLogger
    ) {}

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'patient.allergies',
            'doctor',
            'vitalSigns',
            'diagnoses',
            'prescriptions.items',
            'investigationOrders',
        ]);

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();

        return view('admin.opd.consultation', compact('appointment', 'encounter'));
    }

    public function storeVitalSigns(StoreVitalSignRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $vitalSign = $this->opdService->recordVitalSigns($appointment, $request->validated(), $request->user(), $encounter);

        $this->auditLogger->log('CREATE', VitalSign::class, $vitalSign->id, null, $vitalSign->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Vital signs recorded successfully.');
    }

    public function storeDiagnosis(StoreDiagnosisRequest $request)
    {
        $appointment = Appointment::with('patient')->findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $diagnosis = $this->opdService->addDiagnosis($appointment, $data, $request->user(), $encounter);

        $this->auditLogger->log('CREATE', Diagnosis::class, $diagnosis->id, null, $diagnosis->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Diagnosis added successfully.');
    }

    public function storeInvestigationOrder(StoreInvestigationOrderRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $order = $this->opdService->addInvestigationOrder($appointment, $data, $request->user(), $encounter);

        $this->auditLogger->log('CREATE', InvestigationOrder::class, $order->id, null, $order->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Investigation order created successfully.');
    }

    public function storePrescription(StorePrescriptionRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();
        $prescription = $this->opdService->createPrescription($appointment, $data, $request->user(), $encounter);

        $this->auditLogger->log('CREATE', Prescription::class, $prescription->id, null, $prescription->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Prescription created successfully.');
    }

    /**
     * The actual "Start Consultation" trigger — this is where the Encounter gets created (or
     * re-fetched, idempotently, if the doctor navigates back to an already-started consultation).
     */
    public function markInProgress(Appointment $appointment)
    {
        $this->authorize('checkIn', $appointment);

        $this->lifecycle->startConsultation($appointment, auth()->user());
        $this->encounterService->findOrCreateFromAppointment($appointment, auth()->user());

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Consultation started.');
    }

    public function markCompleted(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $this->lifecycle->complete($appointment, auth()->user());

        $encounter = Encounter::where('appointment_id', $appointment->id)->first();

        if ($encounter && $this->encounterLifecycle->canTransition($encounter, 'completed')) {
            $this->encounterLifecycle->complete($encounter, auth()->user());
        }

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Appointment completed.');
    }

    public function checkIn(Appointment $appointment)
    {
        $this->authorize('checkIn', $appointment);

        $this->lifecycle->checkIn($appointment, auth()->user());

        return back()->with('success', 'Patient checked in successfully.');
    }

    public function createFollowUp(Request $request, Appointment $appointment)
    {
        $this->authorize('create', Appointment::class);

        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $followUp = $this->bookingService->bookFollowUp($appointment, $validated, $request->user());

        return redirect()->route('admin.appointments.show', $followUp)->with('success', 'Follow-up appointment created successfully.');
    }
}
