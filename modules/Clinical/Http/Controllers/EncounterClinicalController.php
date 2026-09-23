<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Models\EncounterAmendment;
use App\Models\Prescription;
use App\Services\AuditLogger;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\EncounterLifecycleService;
use Illuminate\Http\Request;
use Modules\Clinical\Http\Requests\StoreEncounterComplaintRequest;
use Modules\Clinical\Http\Requests\StoreEncounterHistoryRequest;
use Modules\Clinical\Http\Requests\StoreEncounterExaminationRequest;
use Modules\Clinical\Http\Requests\StoreEncounterReviewOfSystemRequest;
use Modules\Clinical\Http\Requests\StorePatientProblemRequest;
use Modules\Clinical\Http\Requests\StoreEncounterProcedureRequest;
use Modules\Clinical\Http\Requests\StoreClinicalOrderRequest;
use Modules\Clinical\Http\Requests\StoreEncounterReferralRequest;
use Modules\Clinical\Http\Requests\StoreEncounterInstructionRequest;
use Modules\Clinical\Http\Requests\StoreEncounterNoteRequest;
use Modules\Clinical\Http\Requests\StoreEncounterDocumentRequest;
use Modules\Clinical\Http\Requests\StoreEncounterAmendmentRequest;
use Modules\Clinical\Http\Requests\StoreEncounterVitalRequest;
use Modules\Clinical\Http\Requests\StoreEncounterDiagnosisRequest;
use Modules\Clinical\Http\Requests\StoreEncounterPrescriptionRequest;

class EncounterClinicalController extends Controller
{
    public function __construct(
        private EncounterClinicalService $clinicalService,
        private EncounterLifecycleService $lifecycleService,
        private AuditLogger $auditLogger,
    ) {}

    public function start(Request $request, Encounter $encounter)
    {
        $this->authorize('startStop', $encounter);

        $this->lifecycleService->start($encounter, $request->user());

        return back()->with('success', 'Encounter started successfully.');
    }

    public function pause(Request $request, Encounter $encounter)
    {
        $this->authorize('startStop', $encounter);

        $this->lifecycleService->pause($encounter, $request->user());

        return back()->with('success', 'Encounter paused successfully.');
    }

    public function resume(Request $request, Encounter $encounter)
    {
        $this->authorize('startStop', $encounter);

        $this->lifecycleService->resume($encounter, $request->user());

        return back()->with('success', 'Encounter resumed successfully.');
    }

    public function complete(Request $request, Encounter $encounter)
    {
        $this->authorize('complete', $encounter);

        $this->lifecycleService->complete($encounter, $request->user());

        return back()->with('success', 'Encounter completed and locked.');
    }

    public function lock(Request $request, Encounter $encounter)
    {
        $this->authorize('lock', $encounter);

        $this->lifecycleService->lock($encounter, $request->user());

        return back()->with('success', 'Encounter locked successfully.');
    }

    public function cancel(Request $request, Encounter $encounter)
    {
        $this->authorize('cancel', $encounter);

        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        $this->lifecycleService->cancel($encounter, $validated['reason'] ?? null, $request->user());

        return back()->with('success', 'Encounter cancelled successfully.');
    }

    public function transfer(Request $request, Encounter $encounter)
    {
        $this->authorize('startStop', $encounter);

        $this->lifecycleService->transfer($encounter, $request->user());

        return back()->with('success', 'Encounter transferred successfully.');
    }

    public function storeVital(StoreEncounterVitalRequest $request, Encounter $encounter)
    {
        $this->authorize('manageVitals', $encounter);

        $vital = $this->clinicalService->addVital($encounter, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', \App\Models\VitalSign::class, $vital->id, null, $vital->toArray(), $request);

        return back()->with('success', 'Vitals recorded successfully.');
    }

    public function storeDiagnosis(StoreEncounterDiagnosisRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDiagnosis', $encounter);

        $diagnosis = $this->clinicalService->addDiagnosis($encounter, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Diagnosis::class, $diagnosis->id, null, $diagnosis->toArray(), $request);

        return back()->with('success', 'Diagnosis added successfully.');
    }

    public function storePrescription(StoreEncounterPrescriptionRequest $request, Encounter $encounter)
    {
        $this->authorize('createPrescription', $encounter);

        $prescription = $this->clinicalService->createPrescription($encounter, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', Prescription::class, $prescription->id, null, $prescription->toArray(), $request);

        return back()->with('success', 'Prescription drafted successfully.');
    }

    public function issuePrescription(Request $request, Encounter $encounter, Prescription $prescription)
    {
        $this->authorize('issuePrescription', $encounter);

        $oldValues = $prescription->toArray();
        $this->clinicalService->issuePrescription($encounter, $prescription, $request->user());

        $this->auditLogger->log('ISSUE', Prescription::class, $prescription->id, $oldValues, $prescription->fresh()->toArray(), $request);

        return back()->with('success', 'Prescription issued successfully.');
    }

    public function cancelPrescription(Request $request, Encounter $encounter, Prescription $prescription)
    {
        $this->authorize('cancelPrescription', $encounter);

        $oldValues = $prescription->toArray();
        $this->clinicalService->cancelPrescription($encounter, $prescription, $request->user());

        $this->auditLogger->log('CANCEL', Prescription::class, $prescription->id, $oldValues, $prescription->fresh()->toArray(), $request);

        return back()->with('success', 'Prescription cancelled successfully.');
    }

    public function storeComplaint(StoreEncounterComplaintRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addComplaint($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Complaint added successfully.');
    }

    public function storeHistory(StoreEncounterHistoryRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addHistory($encounter, $request->validated(), $request->user());

        return back()->with('success', 'History added successfully.');
    }

    public function storeExamination(StoreEncounterExaminationRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addExamination($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Examination added successfully.');
    }

    public function storeReviewOfSystem(StoreEncounterReviewOfSystemRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addReviewOfSystem($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Review of system added successfully.');
    }

    public function storeProblem(StorePatientProblemRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDiagnosis', $encounter);

        $this->clinicalService->addProblem($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Problem added successfully.');
    }

    public function storeProcedure(StoreEncounterProcedureRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addProcedure($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Procedure added successfully.');
    }

    public function storeOrder(StoreClinicalOrderRequest $request, Encounter $encounter)
    {
        $this->authorize('manageOrder', $encounter);

        $order = $this->clinicalService->createOrder($encounter, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', \App\Models\ClinicalOrder::class, $order->id, null, $order->toArray(), $request);

        return back()->with('success', 'Clinical order created successfully.');
    }

    public function storeReferral(StoreEncounterReferralRequest $request, Encounter $encounter)
    {
        $this->authorize('manageReferral', $encounter);

        $this->clinicalService->addReferral($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Referral created successfully.');
    }

    public function storeInstruction(StoreEncounterInstructionRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addInstruction($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Instruction added successfully.');
    }

    public function storeNote(StoreEncounterNoteRequest $request, Encounter $encounter)
    {
        $this->authorize('manageNote', $encounter);

        $this->clinicalService->addNote($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Note added successfully.');
    }

    public function storeDocument(StoreEncounterDocumentRequest $request, Encounter $encounter)
    {
        $this->authorize('manageDocumentation', $encounter);

        $this->clinicalService->addDocument($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function storeAmendment(StoreEncounterAmendmentRequest $request, Encounter $encounter)
    {
        $this->authorize('requestAmendment', $encounter);

        $amendment = $this->clinicalService->createAmendment($encounter, $request->validated(), $request->user());

        $this->auditLogger->log('CREATE', EncounterAmendment::class, $amendment->id, null, $amendment->toArray(), $request);

        return back()->with('success', 'Amendment submitted successfully.');
    }

    public function approveAmendment(Request $request, Encounter $encounter, EncounterAmendment $amendment)
    {
        $this->authorize('approveAmendment', $encounter);

        abort_unless($amendment->encounter_id === $encounter->id, 404);

        $this->clinicalService->approveAmendment($amendment, $request->user());

        $this->auditLogger->log('APPROVE', EncounterAmendment::class, $amendment->id, null, $amendment->fresh()->toArray(), $request);

        return back()->with('success', 'Amendment approved.');
    }
}
