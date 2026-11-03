<?php

namespace Modules\Mch\Http\Controllers\Web;

use App\Services\Dashboard\DashboardCache;
use App\Services\Mch\MchOverview;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;

class MchHomeController extends MchController
{
    public function __construct(TenantContextResolver $tenant, private readonly MchOverview $overview, private readonly DashboardCache $cache)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'mch.dashboard.view');
        $org = $this->organizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $period = DashboardPeriod::fromRequest($request, '30d', ['7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'mch', $org, $branch, $period, fn () => $this->overview->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.mch.index', ['s' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period]);
    }
}
