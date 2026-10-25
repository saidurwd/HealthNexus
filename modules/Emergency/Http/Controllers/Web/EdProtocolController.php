<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdProtocolRun;
use App\Models\Ed\EdProtocolStep;
use App\Models\Ed\EdVisit;
use App\Services\Ed\EdMciService;
use App\Services\Ed\EdProtocolService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdProtocolController extends Controller
{
    public function __construct(
        private readonly EdProtocolService $protocols,
        private readonly EdMciService $mci,
        private readonly TenantContextResolver $tenant,
    ) {}

    public function start(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.protocol.run');
        $validated = $request->validate(['protocol' => ['required', 'string', 'max:30']]);

        $this->protocols->start($visit, $request->user(), $validated['protocol']);

        return back()->with('success', __('Protocol started.'));
    }

    public function completeStep(Request $request, EdProtocolStep $step): RedirectResponse
    {
        abort_unless($step->organization_id === $this->tenant->getOrganizationId(), 404);
        $visit = $step->run->visit;
        $this->authorizeVisit($request, $visit, 'ed.protocol.run');
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $this->protocols->completeStep($step, $request->user(), $validated['note'] ?? null);

        return back()->with('success', __('Step done.'));
    }

    public function cancel(Request $request, EdProtocolRun $run): RedirectResponse
    {
        abort_unless($run->organization_id === $this->tenant->getOrganizationId(), 404);
        $this->authorizeVisit($request, $run->visit, 'ed.protocol.run');
        $this->protocols->cancel($run);

        return back()->with('success', __('Protocol stopped.'));
    }

    public function injury(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.protocol.run');
        $validated = $request->validate([
            'body_region' => ['required', 'string', 'max:30'], 'injury_type' => ['required', 'string', 'max:30'],
            'severity' => ['required', 'string', 'max:20'], 'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->protocols->recordInjury($visit, $request->user(), $validated);

        return back()->with('success', __('Injury recorded.'));
    }

    public function mci(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('ed.mci.manage'), 403);
        $validated = $request->validate(['action' => ['required', 'in:activate,deactivate'], 'reason' => ['nullable', 'string', 'max:300']]);
        $organizationId = $this->tenant->getOrganizationId();
        $branchId = $this->tenant->getBranchId();
        abort_if(! $branchId, 422);

        $validated['action'] === 'activate'
            ? $this->mci->activate($request->user(), $organizationId, $branchId, $validated['reason'] ?? null)
            : $this->mci->deactivate($request->user(), $organizationId, $branchId);

        return back()->with('success', $validated['action'] === 'activate' ? __('Mass-casualty mode is on.') : __('Mass-casualty mode is off.'));
    }

    private function authorizeVisit(Request $request, EdVisit $visit, string $permission): void
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can($permission), 403);
    }
}
