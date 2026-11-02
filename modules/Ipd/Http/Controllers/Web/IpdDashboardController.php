<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCache;
use App\Services\Ipd\IpdOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdDashboardController extends Controller
{
    public function __construct(private readonly IpdOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('ipd.dashboard.view');
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $r = $this->cache->remember($request->user(), 'ipd', $org, $branch, null, fn () => $this->overview->summary($org, $branch), fresh: $request->boolean('fresh'));

        return view('admin.ipd.dashboard.index', ['summary' => $r['data'], 'asOf' => $r['as_of']]);
    }
}
