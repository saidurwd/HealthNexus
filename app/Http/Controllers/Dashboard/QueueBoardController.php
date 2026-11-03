<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\QueueBoardOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The reception queue board: waits and busy doctors, token numbers only (it can be put on a wall). */
class QueueBoardController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly QueueBoardOverview $overview, private readonly DashboardCache $cache) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('appointments.queue'), 403);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $seconds = (int) config('ux.dashboard.refresh_seconds.board', 60);
        $r = $this->cache->remember($request->user(), 'queue-board', $org, $branch, null, fn () => $this->overview->summary($org, $branch), max(0, min(30, $seconds)), $request->boolean('fresh'));

        return view('admin.dashboard.queue-board', ['s' => $r['data'], 'asOf' => $r['as_of'], 'refresh' => $seconds > 0 ? $seconds : null]);
    }
}
