<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingAssessment;
use App\Models\Nursing\NursingEpisode;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingAssessmentService;
use Illuminate\Http\Request;

class NursingAssessmentController extends Controller
{
    public function __construct(
        private readonly NursingAssessmentService $assessments,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingAssessment::class);

        $validated = $request->validate([
            'assessment_type' => ['required', 'string', 'in:initial,ongoing,shift,discharge'],
            'template_key' => ['nullable', 'string', 'max:255'],
            'sections' => ['nullable', 'array'],
        ]);

        $assessment = $this->assessments->create($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingAssessment::class, $assessment->id, null, $assessment->toArray(), $request);

        return redirect()->route('admin.nursing.episodes.show', $episode)->with('success', 'Assessment created.');
    }

    public function update(Request $request, NursingAssessment $assessment)
    {
        $this->authorize('update', $assessment);

        $validated = $request->validate(['sections' => ['nullable', 'array']]);

        $this->assessments->update($assessment, $validated);

        $this->auditLogger->log('UPDATE', NursingAssessment::class, $assessment->id, null, $validated, $request);

        return back()->with('success', 'Assessment updated.');
    }

    public function finalize(Request $request, NursingAssessment $assessment)
    {
        $this->authorize('finalize', $assessment);

        $this->assessments->finalize($assessment, $request->user());

        $this->auditLogger->log('FINALIZE', NursingAssessment::class, $assessment->id, null, ['status' => 'final'], $request);

        return back()->with('success', 'Assessment finalized.');
    }
}
