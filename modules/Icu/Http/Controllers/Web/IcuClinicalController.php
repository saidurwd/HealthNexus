<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuAssessment;
use App\Models\Icu\IcuDevice;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuInfusion;
use App\Models\Icu\IcuProgressNote;
use App\Services\Icu\IcuAssessmentService;
use App\Services\Icu\IcuDeviceService;
use App\Services\Icu\IcuFlowsheetService;
use App\Services\Icu\IcuFluidBalanceService;
use App\Services\Icu\IcuHandoverService;
use App\Services\Icu\IcuInfusionService;
use App\Services\Icu\IcuNutritionService;
use App\Services\Icu\IcuObservationService;
use App\Services\Icu\IcuProgressNoteService;
use App\Services\Icu\IcuRespiratorySupportService;
use App\Services\Icu\IcuScoringService;
use App\Services\Icu\IcuVentilatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Consolidates ICU bedside documentation (assessment, observations/flowsheet, progress notes,
 * handover, ventilator, infusions, devices, fluid balance, nutrition, scoring) into one
 * controller operating on an IcuEpisode — mirrors OT's controller-consolidation precedent.
 */
class IcuClinicalController extends Controller
{
    public function __construct(
        private readonly IcuAssessmentService $assessments,
        private readonly IcuObservationService $observations,
        private readonly IcuFlowsheetService $flowsheet,
        private readonly IcuProgressNoteService $notes,
        private readonly IcuHandoverService $handovers,
        private readonly IcuVentilatorService $ventilator,
        private readonly IcuRespiratorySupportService $respiratory,
        private readonly IcuInfusionService $infusions,
        private readonly IcuDeviceService $devices,
        private readonly IcuFluidBalanceService $fluidBalance,
        private readonly IcuNutritionService $nutrition,
        private readonly IcuScoringService $scoring,
    ) {}

    public function assessment(IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        return view('admin.icu.clinical.assessment', ['episode' => $episode->load('assessments')]);
    }

    public function storeAssessment(Request $request, IcuEpisode $episode)
    {
        $this->authorize('create', IcuAssessment::class);

        $validated = $request->validate(['sections' => ['nullable', 'array']]);
        $assessment = $this->assessments->create($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Assessment recorded.');
    }

    public function finalizeAssessment(Request $request, IcuAssessment $assessment)
    {
        $this->authorize('finalize', $assessment);

        try {
            $this->assessments->finalize($assessment, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $assessment->episode_id)->with('success', 'Assessment finalized.');
    }

    public function flowsheet(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : now()->subDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : now();
        $interval = $request->input('interval', 'hourly');

        $grid = $this->flowsheet->build($episode, $from, $to, $interval);

        return view('admin.icu.clinical.flowsheet', compact('episode', 'grid'));
    }

    public function storeObservation(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'observation_type' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'value_numeric' => ['nullable', 'numeric'],
            'value_text' => ['nullable', 'string'],
            'unit' => ['nullable', 'string'],
            'observed_at' => ['nullable', 'date'],
        ]);

        $this->observations->record($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Observation recorded.');
    }

    public function storeProgressNote(Request $request, IcuEpisode $episode)
    {
        $this->authorize('create', IcuProgressNote::class);

        $validated = $request->validate([
            'note_type' => ['nullable', 'string', 'in:progress,rounds'],
            'overnight_events' => ['nullable', 'string'],
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
        ]);

        $note = $this->notes->create($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Progress note saved.');
    }

    public function finalizeProgressNote(Request $request, IcuProgressNote $note)
    {
        $this->authorize('finalize', $note);

        try {
            $this->notes->finalize($note, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $note->episode_id)->with('success', 'Progress note finalized.');
    }

    public function prepareHandover(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate(['handover_type' => ['nullable', 'string']]);
        $handover = $this->handovers->prepare($episode, $request->user(), $validated['handover_type'] ?? 'shift');
        $this->handovers->finalize($handover);

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Handover prepared.');
    }

    public function acknowledgeHandover(Request $request, int $handover)
    {
        $record = \App\Models\Icu\IcuHandoverRecord::findOrFail($handover);
        $this->authorize('view', $record->episode);

        try {
            $this->handovers->acknowledge($record, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $record->episode_id)->with('success', 'Handover acknowledged.');
    }

    public function storeVentilatorEvent(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'event_type' => ['required', 'string'],
            'mode' => ['nullable', 'string'],
            'fio2' => ['nullable', 'numeric'],
            'peep' => ['nullable', 'numeric'],
            'tidal_volume' => ['nullable', 'numeric'],
            'respiratory_rate' => ['nullable', 'numeric'],
        ]);

        $this->ventilator->recordEvent($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Ventilator event recorded.');
    }

    public function storeRespiratorySupport(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'device' => ['required', 'string'],
            'flow_rate' => ['nullable', 'numeric'],
            'fio2' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string'],
        ]);

        $this->respiratory->start($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Respiratory support recorded.');
    }

    public function storeInfusion(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'medication' => ['required', 'string'],
            'concentration' => ['nullable', 'string'],
            'initial_rate' => ['nullable', 'numeric'],
            'rate_unit' => ['nullable', 'string'],
            'route' => ['nullable', 'string'],
            'prescriber_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $this->infusions->start($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Infusion started.');
    }

    public function titrateInfusion(Request $request, IcuInfusion $infusion)
    {
        $this->authorize('view', $infusion->episode);

        $validated = $request->validate(['new_rate' => ['required', 'numeric'], 'reason' => ['nullable', 'string']]);

        try {
            $this->infusions->titrate($infusion, (float) $validated['new_rate'], $request->user(), $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $infusion->episode_id)->with('success', 'Infusion titrated.');
    }

    public function stopInfusion(Request $request, IcuInfusion $infusion)
    {
        $this->authorize('view', $infusion->episode);

        try {
            $this->infusions->stop($infusion, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $infusion->episode_id)->with('success', 'Infusion stopped.');
    }

    public function storeDevice(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'device_type' => ['required', 'string'],
            'site' => ['nullable', 'string'],
            'side' => ['nullable', 'string'],
            'indication' => ['nullable', 'string'],
            'insertion_date' => ['nullable', 'date'],
        ]);

        $this->devices->insert($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Device recorded.');
    }

    public function removeDevice(Request $request, IcuDevice $device)
    {
        $this->authorize('view', $device->episode);

        $this->devices->remove($device, $request->user(), $request->input('complication'));

        return redirect()->route('admin.icu.episodes.show', $device->episode_id)->with('success', 'Device removed.');
    }

    public function storeFluidBalance(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:intake,output'],
            'category' => ['required', 'string'],
            'volume_ml' => ['required', 'numeric'],
        ]);

        $this->fluidBalance->recordEntry($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Fluid balance entry recorded.');
    }

    public function storeNutrition(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'route' => ['required', 'string'],
            'feeding_type' => ['nullable', 'string'],
            'rate' => ['nullable', 'numeric'],
            'plan' => ['nullable', 'string'],
        ]);

        $this->nutrition->record($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Nutrition plan recorded.');
    }

    public function calculateScore(Request $request, IcuEpisode $episode)
    {
        $this->authorize('view', $episode);

        $validated = $request->validate([
            'score_code' => ['required', 'string'],
            'inputs' => ['required', 'array'],
        ]);

        try {
            $this->scoring->calculate($episode, $validated['score_code'], $validated['inputs'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Clinical score calculated.');
    }
}
