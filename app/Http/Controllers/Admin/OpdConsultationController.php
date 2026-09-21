<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiagnosisRequest;
use App\Http\Requests\Admin\StoreInvestigationOrderRequest;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Http\Requests\Admin\StoreVitalSignRequest;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\InvestigationOrder;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\AuditLogger;
use App\Services\Appointments\AppointmentService;
use App\Services\Appointments\OpdService;
use Illuminate\Http\Request;

class OpdConsultationController extends Controller
{
    public function __construct(
        private OpdService $opdService,
        private AppointmentService $appointmentService,
        private AuditLogger $auditLogger
    ) {}

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'patient',
            'doctor',
            'vitalSigns',
            'diagnoses',
            'prescriptions.items',
            'investigationOrders',
        ]);

        return view('admin.opd.consultation', compact('appointment'));
    }

    public function storeVitalSigns(StoreVitalSignRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $vitalSign = $this->opdService->recordVitalSigns($appointment, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', \App\Models\VitalSign::class, $vitalSign->id, null, $vitalSign->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Vital signs recorded successfully.');
    }

    public function storeDiagnosis(StoreDiagnosisRequest $request)
    {
        $appointment = Appointment::with('patient')->findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);
        $diagnosis = $this->opdService->addDiagnosis($appointment, $data, $request->user());

        $this->auditLogger->log('CREATE', Diagnosis::class, $diagnosis->id, null, $diagnosis->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Diagnosis added successfully.');
    }

    public function storeInvestigationOrder(StoreInvestigationOrderRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);
        $order = $this->opdService->addInvestigationOrder($appointment, $data, $request->user());

        $this->auditLogger->log('CREATE', InvestigationOrder::class, $order->id, null, $order->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Investigation order created successfully.');
    }

    public function storePrescription(StorePrescriptionRequest $request)
    {
        $appointment = Appointment::findOrFail($request->input('appointment_id'));
        $this->authorize('view', $appointment);

        $data = $request->validated();
        unset($data['company_id'], $data['branch_id']);
        $prescription = $this->opdService->createPrescription($appointment, $data, $request->user());

        $this->auditLogger->log('CREATE', Prescription::class, $prescription->id, null, $prescription->toArray(), $request);

        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Prescription created successfully.');
    }

    public function markInProgress(Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $this->opdService->updateAppointmentStatus($appointment, 'in_progress');
        return back()->with('success', 'Appointment marked as in progress.');
    }

    public function markCompleted(Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $this->opdService->updateAppointmentStatus($appointment, 'completed');
        return redirect()->route('admin.opd.consultation', $appointment)->with('success', 'Appointment completed.');
    }

    public function checkIn(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $this->appointmentService->checkIn($appointment);

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

        $followUp = $this->appointmentService->createFollowUp($appointment, $validated, $request->user());

        return redirect()->route('admin.appointments.show', $followUp)->with('success', 'Follow-up appointment created successfully.');
    }
}
