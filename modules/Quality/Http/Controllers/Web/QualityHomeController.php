<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Services\Quality\DocumentService;
use App\Services\Quality\QualityOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The quality home: figures for the quality team; for everyone else a way to report an incident and the documents waiting to be read. */
class QualityHomeController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly QualityOverview $overview, private readonly DocumentService $documents)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'quality.incident.report', 'quality.dashboard.view');
        $summary = $request->user()->can('quality.dashboard.view') ? $this->overview->summary($request->user(), $this->organizationId(), (int) config('quality.dashboard.default_months', 6)) : null;

        return view('admin.quality.index', ['summary' => $summary, 'toRead' => $this->documents->pendingFor($request->user(), $this->organizationId())->count()]);
    }
}
