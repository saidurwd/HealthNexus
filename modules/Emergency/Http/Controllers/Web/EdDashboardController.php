<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdConsult;
use App\Models\Ed\EdProtocolStep;
use App\Models\Ed\EdVisit;
use App\Services\Ed\EdKpiService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class EdDashboardController extends Controller
{
    public function __construct(private readonly EdKpiService $kpis, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('ed.kpi.view'), 403);
        $validated = $request->validate(['days' => ['nullable', 'integer', 'in:1,7,30,90']]);
        $days = (int) ($validated['days'] ?? 7);

        return view('admin.ed.dashboard', [
            'days' => $days,
            'kpi' => $this->kpis->summary($this->tenant->getOrganizationId(), $this->tenant->getBranchId(), now()->subDays($days), now()),
        ]);
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
