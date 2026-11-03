<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuCriticalEvent;
use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuCriticalEventService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuCriticalEventController extends Controller
{
    public function __construct(private readonly IcuCriticalEventService $events) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IcuCriticalEvent::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $events = IcuCriticalEvent::forTenant($organizationId, $branchId)
            ->with('episode.patient')
            ->latest('event_time')
            ->paginate(20)
            ->withQueryString();

        return view('admin.icu.critical-events.index', compact('events'));
    }

    public function store(Request $request, IcuEpisode $episode)
    {
        $this->authorize('create', IcuCriticalEvent::class);

        $validated = $request->validate([
            'event_type' => ['required', 'string'],
            'location' => ['nullable', 'string'],
            'event_time' => ['required', 'date'],
            'initial_condition' => ['nullable', 'string'],
            'interventions' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
        ]);

        $event = $this->events->record($episode, $validated, $request->user());

        return redirect()->route('admin.icu.critical-events.index')->with('success', __('Critical event documented.'));
    }

    public function finalize(Request $request, IcuCriticalEvent $event)
    {
        $this->authorize('finalize', $event);

        try {
            $this->events->finalize($event, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.critical-events.index')->with('success', __('Critical event finalized.'));
    }
}
