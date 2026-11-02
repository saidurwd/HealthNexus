<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdConsult;
use App\Models\Ed\EdProtocolStep;
use App\Models\Ed\EdVisit;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\Targets;
use App\Services\Ed\EdPerformance;
use App\Services\TenantContextResolver;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;

class EdDashboardController extends Controller
{
    private const PERIODS = ['today', '7d', '30d', '90d', 'custom'];

    public function __construct(private readonly TenantContextResolver $tenant, private readonly EdPerformance $performance, private readonly DashboardCache $cache, private readonly Targets $targets) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('ed.kpi.view'), 403);
        $request->validate(['days' => ['nullable', 'integer', 'in:1,7,30,90']]);
        // Older links carry ?days=N; they mean the same as the matching period.
        $period = $request->filled('days') && ! $request->filled('period')
            ? DashboardPeriod::make([1 => 'today', 7 => '7d', 30 => '30d', 90 => '90d'][(int) $request->input('days')], null, null, '7d', self::PERIODS)
            : DashboardPeriod::fromRequest($request, '7d', self::PERIODS);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $r = $this->cache->remember($request->user(), 'ed-performance', $org, $branch, $period, fn () => $this->performance->summary($org, $branch, $period), fresh: $request->boolean('fresh'));

        return view('admin.ed.dashboard', ['days' => $period->days(), 'period' => $period, 'periods' => self::PERIODS, 'kpi' => $r['data']['kpi'], 'previous' => $r['data']['previous'], 'perDay' => $r['data']['per_day'], 'byHour' => $r['data']['by_hour'], 'asOf' => $r['as_of'],
            'targets' => ['triage' => $this->targets->get('ed_door_to_triage'), 'doctor' => $this->targets->get('ed_door_to_doctor'), 'seen' => $this->targets->get('ed_seen_within_target'), 'lwbs' => $this->targets->get('ed_lwbs')]]);
    }

    /** One screen for the person on shift: their own patients and what needs them now. */
    public function workspace(Request $request)
    {
        abort_unless($request->user()->can('ed.board.view'), 403);
        $user = $request->user();
        $organizationId = $this->tenant->getOrganizationId();
        $branchId = $this->tenant->getBranchId();

        $mine = EdVisit::query()->forTenant($organizationId, $branchId)->active()->where(fn ($q) => $q->where('doctor_id', $user->id)->orWhere('nurse_id', $user->id))
            ->with(['patient', 'bay'])->orderByRaw('triage_level is null, triage_level')->orderBy('arrival_at')->get();
        $mineIds = $mine->pluck('id');

        return view('admin.ed.workspace', [
            'mine' => $mine,
            'waitingTriage' => EdVisit::query()->forTenant($organizationId, $branchId)->active()->where('status', EdVisit::AWAITING_TRIAGE)->count(),
            'unseen' => EdVisit::query()->forTenant($organizationId, $branchId)->active()->whereNull('doctor_seen_at')->whereNotNull('triaged_at')->count(),
            'overdueSteps' => EdProtocolStep::query()->whereNull('done_at')->where('due_at', '<=', now())->whereHas('run', fn ($q) => $q->where('status', 'active')->whereIn('visit_id', $mineIds))->count(),
            'openConsults' => EdConsult::query()->where('status', EdConsult::REQUESTED)->whereIn('visit_id', $mineIds)->count(),
        ]);
    }
}
