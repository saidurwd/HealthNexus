<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtOperativeNote;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalComplication;
use App\Models\Ot\OtSurgicalCount;
use App\Services\AuditLogger;
use App\Services\Ot\OtOperativeNoteService;
use App\Services\Ot\OtSurgicalComplicationService;
use App\Services\Ot\OtSurgicalCountService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtOperativeController extends Controller
{
    public function __construct(
        private readonly OtOperativeNoteService $notes,
        private readonly OtSurgicalComplicationService $complications,
        private readonly OtSurgicalCountService $counts,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function storeNote(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        $validated = $request->validate([
            'preoperative_diagnosis' => ['nullable', 'string'],
            'postoperative_diagnosis' => ['nullable', 'string'],
            'anesthesia_type' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'findings' => ['nullable', 'string'],
            'technique_summary' => ['nullable', 'string'],
            'estimated_blood_loss' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $note = $this->notes->create($surgery, $validated, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CREATE', OtOperativeNote::class, $note->id, null, $note->toArray(), $request);

        return back()->with('success', __('Operative note saved.'));
    }

    public function finalizeNote(Request $request, OtOperativeNote $note)
    {
        $this->authorize('finalize', $note);

        try {
            $this->notes->finalize($note, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('FINALIZE', OtOperativeNote::class, $note->id, null, ['status' => 'final'], $request);

        return back()->with('success', __('Operative note finalized.'));
    }

    public function amendNote(Request $request, OtOperativeNote $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate(['content' => ['required', 'string'], 'reason' => ['required', 'string']]);

        try {
            $this->notes->addAddendum($note, $validated['content'], $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', __('Addendum added.'));
    }

    public function storeComplication(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        $validated = $request->validate([
            'complication' => ['required', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:minor,moderate,severe,critical'],
            'phase' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
        ]);

        $complication = $this->complications->record($surgery, $validated, $request->user());

        $this->auditLogger->log('CREATE', OtSurgicalComplication::class, $complication->id, null, $complication->toArray(), $request);

        return back()->with('success', __('Complication recorded.'));
    }

    public function storeCount(Request $request, OtSurgery $surgery)
    {
        $this->authorize('update', $surgery);

        $validated = $request->validate([
            'count_type' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', 'in:initial,additional,final'],
            'count_value' => ['required', 'string', 'max:255'],
            'discrepancy' => ['nullable', 'boolean'],
            'discrepancy_notes' => ['nullable', 'string'],
        ]);

        $count = $this->counts->record($surgery, $validated, $request->user());

        $this->auditLogger->log('CREATE', OtSurgicalCount::class, $count->id, null, $count->toArray(), $request);

        return back()->with('success', __('Count recorded.'));
    }
}
