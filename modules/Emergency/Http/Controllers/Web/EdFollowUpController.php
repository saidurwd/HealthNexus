<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdConsult;
use App\Models\Ed\EdObservationStay;
use App\Models\Ed\EdVisit;
use App\Services\Ed\EdConsultService;
use App\Services\Ed\EdObservationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Consultations and the observation unit. */
class EdFollowUpController extends Controller
{
    public function __construct(
        private readonly EdConsultService $consults,
        private readonly EdObservationService $observation,
        private readonly TenantContextResolver $tenant,
    ) {}

    public function requestConsult(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.consult.manage');
        $validated = $request->validate([
            'specialty' => ['required', 'string', 'max:100'], 'urgency' => ['required', 'in:'.implode(',', array_keys(config('ed.consult_sla')))],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->consults->request($visit, $request->user(), $validated['specialty'], $validated['urgency'], $validated['reason'] ?? null);

        return back()->with('success', __('Consultation requested.'));
    }

    public function respond(Request $request, EdConsult $consult): RedirectResponse
    {
        $this->authorizeVisit($request, $consult->visit, 'ed.consult.manage');
        $validated = $request->validate(['response_note' => ['nullable', 'string', 'max:1000']]);

        $this->consults->respond($consult, $request->user(), $validated['response_note'] ?? null);

        return back()->with('success', __('Answer recorded.'));
    }

    public function cancelConsult(Request $request, EdConsult $consult): RedirectResponse
    {
        $this->authorizeVisit($request, $consult->visit, 'ed.consult.manage');
        $this->consults->cancel($consult);

        return back()->with('success', __('Consultation cancelled.'));
    }

    public function startObservation(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.observation.manage');
        $this->observation->start($visit, $request->user());

        return back()->with('success', __('Moved to the observation unit.'));
    }

    public function endObservation(Request $request, EdObservationStay $stay): RedirectResponse
    {
        $this->authorizeVisit($request, $stay->visit, 'ed.observation.manage');
        $this->observation->end($stay, $request->user());

        return back()->with('success', __('Observation ended.'));
    }

    private function authorizeVisit(Request $request, EdVisit $visit, string $permission): void
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can($permission), 403);
    }
}
