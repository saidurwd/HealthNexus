<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrder;
use App\Services\Dashboard\DashboardCache;
use App\Services\Lab\LabOverview;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabDashboardController extends Controller
{
    public function __construct(private readonly LabOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', LabOrder::class);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $period = DashboardPeriod::fromRequest($request, '7d', ['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'lab', $org, $branch, $period, fn () => $this->overview->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.lab.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period]);
    }
}
