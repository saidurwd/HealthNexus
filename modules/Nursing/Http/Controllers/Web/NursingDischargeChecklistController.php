<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingDischargeChecklist;
use App\Models\Nursing\NursingEpisode;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingDischargeChecklistService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NursingDischargeChecklistController extends Controller
{
    public function __construct(
        private readonly NursingDischargeChecklistService $checklists,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(NursingEpisode $episode)
    {
        $this->authorize('create', NursingDischargeChecklist::class);

        $checklist = $this->checklists->create($episode, $episode->admission?->dischargeRequests()->latest()->first());

        return view('admin.nursing.discharge-checklist.show', compact('checklist'));
    }

    public function update(Request $request, NursingDischargeChecklist $checklist)
    {
        $this->authorize('complete', $checklist);

        $validated = $request->validate([
            'education_completed' => ['nullable', 'boolean'],
            'medication_education_completed' => ['nullable', 'boolean'],
            'devices_removed' => ['nullable', 'boolean'],
            'belongings_confirmed' => ['nullable', 'boolean'],
            'follow_up_instructions_given' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->checklists->updateItem($checklist, $validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('UPDATE', NursingDischargeChecklist::class, $checklist->id, null, $validated, $request);

        return back()->with('success', 'Checklist updated.');
    }

    public function complete(Request $request, NursingDischargeChecklist $checklist)
    {
        $this->authorize('complete', $checklist);

        try {
            $this->checklists->complete($checklist, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', NursingDischargeChecklist::class, $checklist->id, null, ['status' => 'completed'], $request);

        return back()->with('success', 'Nursing discharge clearance completed.');
    }
}
