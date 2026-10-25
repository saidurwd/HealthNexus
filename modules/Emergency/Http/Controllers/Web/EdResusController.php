<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdVisit;
use App\Services\Ed\EdMonitoringService;
use App\Services\Ed\EdResuscitationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdResusController extends Controller
{
    public function __construct(
        private readonly EdResuscitationService $resus,
        private readonly EdMonitoringService $monitoring,
        private readonly TenantContextResolver $tenant,
    ) {}

    public function event(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.resus.record');
        $validated = $request->validate(['event_type' => ['required', 'in:'.implode(',', config('ed.resus_events'))], 'detail' => ['nullable', 'string', 'max:500']]);

        $validated['event_type'] === 'code_blue_called'
            ? $this->resus->callCodeBlue($visit, $request->user(), $validated['detail'] ?? null)
            : $this->resus->record($visit, $request->user(), $validated['event_type'], $validated['detail'] ?? null);

        return back()->with('success', __('Recorded.'));
    }

    public function monitor(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.monitor.record');
        $validated = $request->validate(['vitals' => ['required', 'array'], 'vitals.*' => ['nullable', 'numeric']]);

        $breaches = $this->monitoring->record($visit, $request->user(), $validated['vitals']);

        return back()->with($breaches ? 'warning' : 'success', $breaches ? __('Recorded. Some readings are outside the safe range — the team has been alerted.') : __('Recorded.'));
    }

    private function authorizeVisit(Request $request, EdVisit $visit, string $permission): void
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can($permission), 403);
    }
}
