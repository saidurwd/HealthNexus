<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCache;
use App\Services\Pharmacy\PharmacyOverview;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyDashboardController extends Controller
{
    public function __construct(private readonly PharmacyOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.dashboard.view');
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $period = DashboardPeriod::fromRequest($request, '7d', ['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'pharmacy', $org, $branch, $period, fn () => $this->overview->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.pharmacy.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period]);
    }
}
