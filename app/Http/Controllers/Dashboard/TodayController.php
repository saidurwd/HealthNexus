<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\TodayOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** "Today in the hospital": the director's morning figures. Totals only, no patient. */
class TodayController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly TodayOverview $overview, private readonly DashboardCache $cache) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('dashboard.today.view'), 403);
        $org = (int) $this->tenant->getOrganizationId();
        $r = $this->cache->remember($request->user(), 'today', $org, null, null, fn () => $this->overview->summary($org), fresh: $request->boolean('fresh'));

        return view('admin.dashboard.today', ['s' => $r['data'], 'asOf' => $r['as_of'], 'can' => [
            'opd' => $request->user()->can('encounters.view'), 'ed' => $request->user()->can('ed.board.view'), 'ipd' => $request->user()->can('ipd.dashboard.view'), 'ot' => $request->user()->can('ot.dashboard.view'),
            'billing' => $request->user()->can('billing.dashboard.view'), 'adjust' => $request->user()->can('billing.adjustment.approve'), 'welfare' => $request->user()->can('welfare.grant.view')]]);
    }
}
