<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Services\Dashboard\DashboardCache;
use App\Services\Ot\OtOverview;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\Days;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtDashboardController extends Controller
{
    public function __construct(private readonly OtOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        Gate::authorize('ot.dashboard.view');
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $r = $this->cache->remember($request->user(), 'ot', $org, $branch, null, fn () => $this->overview->summary($org, $branch), fresh: $request->boolean('fresh'));
        // Today's list names patients and surgeons: a worklist for the theatre team, not part of the cached figures.
        $todaysList = OtSurgery::forTenant($org, $branch)->whereDate('scheduled_date', Days::today()->toDateString())->with(['patient', 'theatre', 'primarySurgeon', 'primaryProcedure'])->orderBy('scheduled_start_at')->get();

        return view('admin.ot.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'todaysList' => $todaysList]);
    }
}
