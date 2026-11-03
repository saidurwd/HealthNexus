<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuFamilyCommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IcuFamilyCommunicationController extends Controller
{
    public function __construct(private readonly IcuFamilyCommunicationService $communications) {}

    public function store(Request $request, IcuEpisode $episode)
    {
        Gate::authorize('icu.family_communication.create');

        $validated = $request->validate([
            'family_contact' => ['nullable', 'string'],
            'relationship' => ['nullable', 'string'],
            'communication_type' => ['nullable', 'string'],
            'clinical_information_shared' => ['nullable', 'string'],
            'treatment_discussion' => ['nullable', 'string'],
            'prognosis_discussion' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
        ]);

        $this->communications->record($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', __('Family communication documented.'));
    }
}
