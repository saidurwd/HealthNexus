<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
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

class EncounterClinicalController extends Controller
{
    public function __construct(
        private EncounterClinicalService $clinicalService,
        private EncounterLifecycleService $lifecycleService
    ) {}

    public function start(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->start($encounter, $request->user());

        return back()->with('success', 'Encounter started successfully.');
    }

    public function pause(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->pause($encounter, $request->user());

        return back()->with('success', 'Encounter paused successfully.');
    }

    public function resume(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->resume($encounter, $request->user());

        return back()->with('success', 'Encounter resumed successfully.');
    }

    public function complete(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->complete($encounter, $request->user());

        return back()->with('success', 'Encounter completed successfully.');
    }

    public function lock(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->lock($encounter, $request->user());

        return back()->with('success', 'Encounter locked successfully.');
    }

    public function cancel(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        $this->lifecycleService->cancel($encounter, $validated['reason'] ?? null, $request->user());

        return back()->with('success', 'Encounter cancelled successfully.');
    }

    public function transfer(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->transfer($encounter, $request->user());

        return back()->with('success', 'Encounter transferred successfully.');
    }

    public function issuePrescription(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->issuePrescription($encounter, $request->user());

        return back()->with('success', 'Prescription issued successfully.');
    }

    public function cancelPrescription(Request $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->cancelPrescription($encounter, $request->user());

        return back()->with('success', 'Prescription cancelled successfully.');
    }

    public function storeComplaint(StoreEncounterComplaintRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addComplaint($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Complaint added successfully.');
    }

    public function storeHistory(StoreEncounterHistoryRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addHistory($encounter, $request->validated(), $request->user());

        return back()->with('success', 'History added successfully.');
    }

    public function storeExamination(StoreEncounterExaminationRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addExamination($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Examination added successfully.');
    }

    public function storeReviewOfSystem(StoreEncounterReviewOfSystemRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addReviewOfSystem($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Review of system added successfully.');
    }

    public function storeProblem(StorePatientProblemRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addProblem($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Problem added successfully.');
    }

    public function storeProcedure(StoreEncounterProcedureRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addProcedure($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Procedure added successfully.');
    }

    public function storeOrder(StoreClinicalOrderRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->createOrder($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Clinical order created successfully.');
    }

    public function storeReferral(StoreEncounterReferralRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addReferral($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Referral created successfully.');
    }

    public function storeInstruction(StoreEncounterInstructionRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addInstruction($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Instruction added successfully.');
    }

    public function storeNote(StoreEncounterNoteRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addNote($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Note added successfully.');
    }

    public function storeDocument(StoreEncounterDocumentRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->addDocument($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function storeAmendment(StoreEncounterAmendmentRequest $request, Encounter $encounter)
    {
        $this->authorize('update', $encounter);

        $this->clinicalService->createAmendment($encounter, $request->validated(), $request->user());

        return back()->with('success', 'Amendment created successfully.');
    }
}
