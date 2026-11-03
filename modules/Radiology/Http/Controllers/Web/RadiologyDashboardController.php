<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Radiology\RadiologyOrder;
use App\Services\Dashboard\DashboardCache;
use App\Services\Radiology\RadiologyOverview;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyDashboardController extends Controller
{
    public function __construct(private readonly RadiologyOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', RadiologyOrder::class);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $period = DashboardPeriod::fromRequest($request, '7d', ['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'radiology', $org, $branch, $period, fn () => $this->overview->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.radiology.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period]);
    }
}
