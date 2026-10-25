<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtPreoperativeAssessment;
use App\Models\Ot\OtPreoperativeChecklist;
use App\Models\Ot\OtPreoperativeChecklistItem;
use App\Models\Ot\OtSurgery;
use App\Models\PatientConsent;
use App\Policies\OtConsentPolicy;
use App\Services\AuditLogger;
use App\Services\Ot\OtConsentService;
use App\Services\Ot\OtPreoperativeAssessmentService;
use App\Services\Ot\OtPreoperativeChecklistService;
use App\Services\Ot\OtVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Assessment, checklist, consent and verification — grouped together since each is a small
 * preoperative form attached to the same surgery (mirrors Nursing's controller-consolidation
 * precedent).
 */
class OtPreoperativeController extends Controller
{
    public function __construct(
        private readonly OtPreoperativeAssessmentService $assessments,
        private readonly OtPreoperativeChecklistService $checklists,
        private readonly OtConsentService $consents,
        private readonly OtVerificationService $verification,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        $checklist = $this->checklists->initialize($surgery);
        $mismatches = $this->verification->verify($surgery);

        return view('admin.ot.preoperative.show', compact('surgery', 'checklist', 'mismatches'));
    }

    public function storeAssessment(Request $request, OtSurgery $surgery)
    {
        $this->authorize('create', OtPreoperativeAssessment::class);

        $validated = $request->validate(['sections' => ['nullable', 'array']]);

        $assessment = $this->assessments->create($surgery, $validated, $request->user());

        $this->auditLogger->log('CREATE', OtPreoperativeAssessment::class, $assessment->id, null, $assessment->toArray(), $request);

        return back()->with('success', 'Preoperative assessment saved.');
    }

    public function finalizeAssessment(Request $request, OtPreoperativeAssessment $assessment)
    {
        $this->authorize('complete', $assessment);

        try {
            $this->assessments->finalize($assessment, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Preoperative assessment finalized.');
    }

    public function completeChecklistItem(Request $request, OtPreoperativeChecklistItem $item)
    {
        Gate::authorize('ot.preop.create');
        $this->authorize('view', $item->checklist->surgery);

        $validated = $request->validate([
            'response' => ['nullable', 'string'],
            'exception_reason' => ['nullable', 'string'],
        ]);

        try {
            $this->checklists->completeItem($item, $request->user(), $validated['response'] ?? null, $validated['exception_reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Checklist item updated.');
    }

    public function completeChecklist(Request $request, OtPreoperativeChecklist $checklist)
    {
        Gate::authorize('ot.preop.complete');
        $this->authorize('view', $checklist->surgery);

        try {
            $this->checklists->complete($checklist, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Preoperative checklist completed.');
    }

    public function storeConsent(Request $request, OtSurgery $surgery)
    {
        if (! app(OtConsentPolicy::class)->create($request->user(), $surgery)) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'witness_name' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:50'],
        ]);

        $consent = $this->consents->grant(
            $surgery,
            $request->user(),
            $validated['notes'] ?? null,
            $validated['witness_name'] ?? null,
            $validated['language'] ?? null,
        );

        $this->auditLogger->log('CREATE', PatientConsent::class, $consent->id, null, $consent->toArray(), $request);

        return back()->with('success', 'Consent recorded.');
    }
}
