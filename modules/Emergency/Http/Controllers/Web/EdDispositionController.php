<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdVisit;
use App\Services\AuditLogger;
use App\Services\Ed\EdDispositionService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdDispositionController extends Controller
{
    public function __construct(
        private readonly EdDispositionService $dispositions,
        private readonly TenantContextResolver $tenant,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request, EdVisit $visit): RedirectResponse
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can('ed.disposition.decide'), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(config('ed.dispositions')))],
            'reason' => ['nullable', 'string', 'max:1000'], 'destination' => ['nullable', 'string', 'max:200'], 'follow_up' => ['nullable', 'string', 'max:500'],
            'time_of_death' => ['nullable', 'date'], 'cause' => ['nullable', 'string', 'max:500'],
            'ambulance_required' => ['sometimes', 'boolean'], 'risks_explained' => ['sometimes', 'boolean'],
        ]);

        $disposition = $this->dispositions->decide($visit, $request->user(), $validated);
        $this->audit->log('ED_DISPOSITION', EdVisit::class, $visit->id, null, ['type' => $disposition->type, 'visit' => $visit->visit_number], $request);

        return redirect()->route('admin.ed.visits.show', $visit)->with('success', __('Visit closed.'));
    }
}
