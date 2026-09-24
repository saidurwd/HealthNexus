<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingFluidBalanceService;
use App\Services\Nursing\NursingObservationService;
use App\Services\Nursing\NursingPainAssessmentService;
use App\Services\Nursing\NursingRiskAssessmentService;
use App\Services\Nursing\NursingVitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Covers vitals (shared VitalSign table), generic observations, pain, risk, and intake/output —
 * grouped together since each is a small append-only form attached to the same episode.
 */
class NursingObservationController extends Controller
{
    public function __construct(
        private readonly NursingVitalService $vitals,
        private readonly NursingObservationService $observations,
        private readonly NursingPainAssessmentService $pain,
        private readonly NursingRiskAssessmentService $risk,
        private readonly NursingFluidBalanceService $fluidBalance,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(NursingEpisode $episode)
    {
        Gate::authorize('nursing.vitals.view');

        $episode->load('encounter');
        $vitals = $episode->encounter?->vitals()->latest('recorded_at')->paginate(20) ?? collect();
        $observations = \App\Models\Nursing\NursingObservation::where('episode_id', $episode->id)->latest('observed_at')->paginate(20, ['*'], 'observations_page');

        return view('admin.nursing.observations.index', compact('episode', 'vitals', 'observations'));
    }

    public function storeVital(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.vitals.create');

        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric'], 'temperature_unit' => ['nullable', 'string'],
            'systolic' => ['nullable', 'integer'], 'diastolic' => ['nullable', 'integer'], 'bp_unit' => ['nullable', 'string'],
            'pulse_rate' => ['nullable', 'integer'], 'respiratory_rate' => ['nullable', 'integer'],
            'height' => ['nullable', 'numeric'], 'weight' => ['nullable', 'numeric'],
            'oxygen_saturation' => ['nullable', 'integer'], 'notes' => ['nullable', 'string'],
        ]);

        $vital = $this->vitals->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\VitalSign::class, $vital->id, null, $vital->toArray(), $request);

        return back()->with('success', 'Vitals recorded.');
    }

    public function storeObservation(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.observation.create');

        $validated = $request->validate([
            'observation_type' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'observed_at' => ['nullable', 'date'],
        ]);

        $observation = $this->observations->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingObservation::class, $observation->id, null, $observation->toArray(), $request);

        return back()->with('success', 'Observation recorded.');
    }

    public function storePain(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.observation.create');

        $validated = $request->validate([
            'scale_type' => ['nullable', 'string'], 'score' => ['required', 'string'],
            'location' => ['nullable', 'string'], 'character' => ['nullable', 'string'],
            'intervention' => ['nullable', 'string'],
        ]);

        $assessment = $this->pain->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingPainAssessment::class, $assessment->id, null, $assessment->toArray(), $request);

        return back()->with('success', 'Pain assessment recorded.');
    }

    public function storeRisk(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.observation.create');

        $validated = $request->validate([
            'risk_type' => ['required', 'string'], 'tool_name' => ['nullable', 'string'],
            'score' => ['nullable', 'string'], 'risk_level' => ['nullable', 'string'],
            'contributing_factors' => ['nullable', 'string'], 'interventions' => ['nullable', 'string'],
        ]);

        $assessment = $this->risk->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingRiskAssessment::class, $assessment->id, null, $assessment->toArray(), $request);

        return back()->with('success', 'Risk assessment recorded.');
    }

    public function intakeOutput(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.observation.view');

        $from = Carbon::parse($request->input('from', now()->subHours(24)));
        $to = Carbon::parse($request->input('to', now()));

        $balance = $this->fluidBalance->netBalance($episode, $from, $to);
        $records = \App\Models\Nursing\NursingIntakeOutputRecord::where('episode_id', $episode->id)
            ->whereBetween('recorded_at', [$from, $to])->latest('recorded_at')->get();

        return view('admin.nursing.observations.intake-output', compact('episode', 'balance', 'records', 'from', 'to'));
    }

    public function storeIntakeOutput(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.observation.create');

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:intake,output'],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'route' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = $validated['type'] === 'intake'
            ? $this->fluidBalance->recordIntake($episode, $validated, $request->user())
            : $this->fluidBalance->recordOutput($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Nursing\NursingIntakeOutputRecord::class, $record->id, null, $record->toArray(), $request);

        return back()->with('success', 'Intake/output recorded.');
    }
}
