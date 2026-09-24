<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingNote;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingNoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NursingNoteController extends Controller
{
    public function __construct(
        private readonly NursingNoteService $notes,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(NursingEpisode $episode)
    {
        $this->authorize('viewAny', NursingNote::class);

        $notes = $episode->notes()->with(['createdBy', 'amendments'])->latest()->paginate(20);

        return view('admin.nursing.notes.index', compact('episode', 'notes'));
    }

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingNote::class);

        $validated = $request->validate([
            'note_type' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $note = $this->notes->create($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingNote::class, $note->id, null, $note->toArray(), $request);

        return back()->with('success', 'Note saved as draft.');
    }

    public function update(Request $request, NursingNote $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate(['content' => ['required', 'string']]);

        try {
            $this->notes->update($note, $validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('UPDATE', NursingNote::class, $note->id, null, $validated, $request);

        return back()->with('success', 'Note updated.');
    }

    public function finalize(Request $request, NursingNote $note)
    {
        $this->authorize('finalize', $note);

        try {
            $this->notes->finalize($note, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('FINALIZE', NursingNote::class, $note->id, null, ['status' => 'final'], $request);

        return back()->with('success', 'Note finalized and signed.');
    }

    public function amend(Request $request, NursingNote $note)
    {
        $this->authorize('amend', $note);

        $validated = $request->validate(['content' => ['required', 'string']]);

        try {
            $amendment = $this->notes->addAddendum($note, $validated['content'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('AMEND', NursingNote::class, $note->id, null, $amendment->toArray(), $request);

        return back()->with('success', 'Addendum added.');
    }
}
