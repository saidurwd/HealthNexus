<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuCriticalAlert;
use App\Services\Dashboard\DashboardCache;
use App\Services\Icu\IcuOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IcuDashboardController extends Controller
{
    public function __construct(private readonly IcuOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('icu.dashboard.view');
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $r = $this->cache->remember($request->user(), 'icu', $org, $branch, null, fn () => $this->overview->summary($org, $branch), fresh: $request->boolean('fresh'));
        // The patients with an alert nobody has answered: a worklist for the clinical team, not part of the cached figures.
        $criticalAlerts = IcuCriticalAlert::forTenant($org, $branch)->whereNull('acknowledged_at')->with('episode.patient')->latest('triggered_at')->limit(10)->get();

        return view('admin.icu.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'criticalAlerts' => $criticalAlerts]);
    }
}
