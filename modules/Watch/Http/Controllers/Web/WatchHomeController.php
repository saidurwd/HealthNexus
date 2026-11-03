<?php

namespace Modules\Watch\Http\Controllers\Web;

use App\Services\Dashboard\DashboardCache;
use App\Services\TenantContextResolver;
use App\Services\Watch\SurveillanceService;
use App\Services\Watch\WatchOverview;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;

class WatchHomeController extends WatchController
{
    public function __construct(TenantContextResolver $tenant, private readonly WatchOverview $overview, private readonly DashboardCache $cache, private readonly SurveillanceService $surveillance)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'watch.dashboard.view');
        $org = $this->organizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $period = DashboardPeriod::fromRequest($request, '30d', ['7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'watch', $org, $branch, $period, fn () => $this->overview->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.watch.index', ['s' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period, 'canScan' => $request->user()->can('watch.signal.decide')]);
    }

    /** Look now, instead of waiting for the daily scan. */
    public function scan(Request $request)
    {
        $this->allow($request, 'watch.signal.decide');
        $r = $this->surveillance->scan($this->organizationId());

        return redirect()->route('admin.watch.signals')->with('success', __(':n new signal(s) raised.', ['n' => $r['raised']]));
    }
}
