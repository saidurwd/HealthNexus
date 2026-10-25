<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuDischargeSummary;
use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuDischargeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuDischargeController extends Controller
{
    public function __construct(private readonly IcuDischargeService $discharge) {}

    public function create(IcuEpisode $episode)
    {
        $this->authorize('create', IcuDischargeSummary::class);

        return view('admin.icu.discharge.create', compact('episode'));
    }

    public function store(Request $request, IcuEpisode $episode)
    {
        $this->authorize('create', IcuDischargeSummary::class);

        $validated = $request->validate([
            'admission_reason_summary' => ['nullable', 'string'],
            'diagnoses' => ['nullable', 'string'],
            'major_events' => ['nullable', 'string'],
            'current_status' => ['nullable', 'string'],
            'destination' => ['required', 'string'],
            'follow_up_plan' => ['nullable', 'string'],
        ]);

        $summary = $this->discharge->start($episode, $validated, $request->user());

        return redirect()->route('admin.icu.discharge.show', $summary)->with('success', 'Discharge summary drafted.');
    }

    public function show(IcuDischargeSummary $summary)
    {
        $this->authorize('view', $summary);

        return view('admin.icu.discharge.show', compact('summary'));
    }

    public function finalize(Request $request, IcuDischargeSummary $summary)
    {
        $this->authorize('finalize', $summary);

        try {
            $summary = $this->discharge->finalize($summary, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.episodes.show', $summary->episode_id)->with('success', 'Patient discharged from ICU.');
    }
}
