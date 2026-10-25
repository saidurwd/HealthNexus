<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalSafetyChecklist;
use App\Models\Ot\OtSurgicalSafetyChecklistItem;
use App\Services\AuditLogger;
use App\Services\Ot\OtSurgicalSafetyChecklistService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtSafetyChecklistController extends Controller
{
    public function __construct(
        private readonly OtSurgicalSafetyChecklistService $checklists,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function initialize(Request $request, OtSurgery $surgery, string $phase)
    {
        $this->authorize('view', $surgery);

        $checklist = $this->checklists->initializePhase($surgery, $phase);

        return back()->with('success', ucwords(str_replace('_', ' ', $phase)).' checklist ready.')->with('checklist_id', $checklist->id);
    }

    public function completeItem(Request $request, OtSurgicalSafetyChecklistItem $item)
    {
        $this->authorize('complete', $item->checklist);

        $validated = $request->validate([
            'response' => ['nullable', 'string'],
            'exception_reason' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $this->checklists->completeItem(
                $item,
                $request->user(),
                $validated['response'] ?? null,
                $validated['exception_reason'] ?? null,
                $validated['comment'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('Checklist item updated.'));
    }

    public function completePhase(Request $request, OtSurgicalSafetyChecklist $checklist)
    {
        $this->authorize('complete', $checklist);

        try {
            $this->checklists->completePhase($checklist, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', OtSurgicalSafetyChecklist::class, $checklist->id, null, ['status' => 'completed'], $request);

        return back()->with('success', ucwords(str_replace('_', ' ', $checklist->phase)).' checklist completed.');
    }
}
