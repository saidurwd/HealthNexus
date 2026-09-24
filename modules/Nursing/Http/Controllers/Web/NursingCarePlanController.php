<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingCarePlan;
use App\Models\Nursing\NursingEpisode;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingCarePlanService;
use Illuminate\Http\Request;

class NursingCarePlanController extends Controller
{
    public function __construct(
        private readonly NursingCarePlanService $carePlans,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(NursingCarePlan $carePlan)
    {
        $this->authorize('view', $carePlan);

        $carePlan->load(['diagnoses', 'goals', 'interventions.responsibleNurse']);

        return view('admin.nursing.care-plans.show', compact('carePlan'));
    }

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingCarePlan::class);

        $carePlan = $this->carePlans->create($episode, $request->user());

        $this->auditLogger->log('CREATE', NursingCarePlan::class, $carePlan->id, null, $carePlan->toArray(), $request);

        return redirect()->route('admin.nursing.care-plans.show', $carePlan)->with('success', 'Care plan created.');
    }

    public function storeDiagnosis(Request $request, NursingCarePlan $carePlan)
    {
        $this->authorize('update', $carePlan);

        $validated = $request->validate([
            'diagnosis_text' => ['required', 'string'],
            'related_factors' => ['nullable', 'string'],
            'evidence' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
        ]);

        $diagnosis = $this->carePlans->addDiagnosis($carePlan, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingDiagnosis::class, $diagnosis->id, null, $diagnosis->toArray(), $request);

        return back()->with('success', 'Nursing diagnosis added.');
    }

    public function storeGoal(Request $request, NursingCarePlan $carePlan)
    {
        $this->authorize('update', $carePlan);

        $validated = $request->validate([
            'nursing_diagnosis_id' => ['nullable', 'integer', 'exists:nursing_diagnoses,id'],
            'goal_text' => ['required', 'string'],
            'target_date' => ['nullable', 'date'],
        ]);

        $goal = $this->carePlans->addGoal($carePlan, $validated);

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingCarePlanGoal::class, $goal->id, null, $goal->toArray(), $request);

        return back()->with('success', 'Goal added.');
    }

    public function storeIntervention(Request $request, NursingCarePlan $carePlan)
    {
        $this->authorize('update', $carePlan);

        $validated = $request->validate([
            'goal_id' => ['nullable', 'integer', 'exists:nursing_care_plan_goals,id'],
            'intervention_type' => ['required', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'responsible_nurse_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $intervention = $this->carePlans->addIntervention($carePlan, $validated);

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingCarePlanIntervention::class, $intervention->id, null, $intervention->toArray(), $request);

        return back()->with('success', 'Intervention added.');
    }

    public function complete(Request $request, NursingCarePlan $carePlan)
    {
        $this->authorize('complete', $carePlan);

        $this->carePlans->complete($carePlan);

        $this->auditLogger->log('COMPLETE', NursingCarePlan::class, $carePlan->id, null, ['status' => 'completed'], $request);

        return back()->with('success', 'Care plan completed.');
    }
}
