<?php

namespace Modules\Ot\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ot\OtOperativeNote;
use App\Models\Ot\OtPreoperativeChecklistItem;
use App\Models\Ot\OtRecoveryRecord;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Ot\OtSurgicalSafetyChecklistItem;
use App\Services\AuditLogger;
use App\Services\Ot\OtOperativeNoteService;
use App\Services\Ot\OtPreoperativeChecklistService;
use App\Services\Ot\OtReceptionService;
use App\Services\Ot\OtRecoveryService;
use App\Services\Ot\OtSurgicalSafetyChecklistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OtClinicalController extends Controller
{
    public function __construct(
        private readonly OtPreoperativeChecklistService $preopChecklist,
        private readonly OtSurgicalSafetyChecklistService $safetyChecklist,
        private readonly OtReceptionService $reception,
        private readonly OtOperativeNoteService $notes,
        private readonly OtRecoveryService $recovery,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function preoperativeChecklist(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        return ApiResponse::success($this->preopChecklist->initialize($surgery));
    }

    public function completePreoperativeChecklistItem(Request $request, OtPreoperativeChecklistItem $item)
    {
        Gate::authorize('ot.preop.create');
        $this->authorize('view', $item->checklist->surgery);

        $validated = $request->validate(['response' => ['nullable', 'string'], 'exception_reason' => ['nullable', 'string']]);

        try {
            $item = $this->preopChecklist->completeItem($item, $request->user(), $validated['response'] ?? null, $validated['exception_reason'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($item);
    }

    public function initializeSafetyChecklist(OtSurgery $surgery, string $phase)
    {
        $this->authorize('view', $surgery);

        return ApiResponse::success($this->safetyChecklist->initializePhase($surgery, $phase), '', 201);
    }

    public function completeSafetyChecklistItem(Request $request, OtSurgicalSafetyChecklistItem $item)
    {
        $this->authorize('complete', $item->checklist);

        $validated = $request->validate(['response' => ['nullable', 'string'], 'exception_reason' => ['nullable', 'string']]);

        try {
            $item = $this->safetyChecklist->completeItem($item, $request->user(), $validated['response'] ?? null, $validated['exception_reason'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($item);
    }

    public function completeSafetyChecklistPhase(Request $request, OtSurgicalSafetyChecklist $checklist)
    {
        $this->authorize('complete', $checklist);

        try {
            $checklist = $this->safetyChecklist->completePhase($checklist, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($checklist);
    }

    public function receive(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        try {
            $surgery = $this->reception->receive($surgery, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($surgery, 'Patient received into theatre.');
    }

    public function start(Request $request, OtSurgery $surgery)
    {
        $this->authorize('start', $surgery);

        try {
            $surgery = $this->reception->start($surgery, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('START', OtSurgery::class, $surgery->id, null, ['status' => 'in_procedure'], $request);

        return ApiResponse::success($surgery, 'Procedure started.');
    }

    public function complete(Request $request, OtSurgery $surgery)
    {
        $this->authorize('complete', $surgery);

        try {
            $surgery = $this->reception->complete($surgery, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('COMPLETE', OtSurgery::class, $surgery->id, null, ['status' => 'procedure_completed'], $request);

        return ApiResponse::success($surgery, 'Procedure marked complete.');
    }

    public function storeOperativeNote(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        $validated = $request->validate([
            'preoperative_diagnosis' => ['nullable', 'string'],
            'postoperative_diagnosis' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'technique_summary' => ['nullable', 'string'],
        ]);

        $note = $this->notes->create($surgery, $validated, $request->user());

        return ApiResponse::success($note, 'Operative note saved.', 201);
    }

    public function finalizeOperativeNote(Request $request, OtOperativeNote $note)
    {
        $this->authorize('finalize', $note);

        try {
            $note = $this->notes->finalize($note, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($note, 'Operative note finalized.');
    }

    public function recovery(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        return ApiResponse::success($this->recovery->admitToRecovery($surgery));
    }

    public function completeRecoveryTransfer(Request $request, OtRecoveryRecord $recovery)
    {
        $this->authorize('complete', $recovery);

        $validated = $request->validate(['destination' => ['required', 'string', 'in:ward,icu,hdu,discharge,other_facility']]);

        $recovery = $this->recovery->completeTransfer($recovery, $validated['destination'], $request->user());

        $this->auditLogger->log('TRANSFER', OtRecoveryRecord::class, $recovery->id, null, $validated, $request);

        return ApiResponse::success($recovery, 'Patient transferred.');
    }
}
