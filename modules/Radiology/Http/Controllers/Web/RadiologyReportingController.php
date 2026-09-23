<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyOrder;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyReportingController extends Controller
{
    public function dailyVolume(Request $request)
    {
        Gate::authorize('radiology.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $volume = RadiologyOrder::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('ordered_at', [$from, $to])
            ->selectRaw('DATE(ordered_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.radiology.analytics.daily-volume', compact('volume', 'from', 'to'));
    }

    public function modalityUtilization(Request $request)
    {
        Gate::authorize('radiology.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        // Joined against radiology_modalities (which also has company_id/branch_id) — the
        // forTenant() scope's unqualified columns would be ambiguous here, so scope explicitly
        // against the examinations table instead.
        $utilization = RadiologyExamination::query()
            ->where('radiology_examinations.company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('radiology_examinations.branch_id', $branchId))
            ->whereBetween('completed_at', [$from, $to])
            ->whereNotNull('modality_id')
            ->join('radiology_modalities', 'radiology_modalities.id', '=', 'radiology_examinations.modality_id')
            ->selectRaw('radiology_modalities.name as modality_name, count(*) as total')
            ->groupBy('radiology_modalities.name')
            ->orderByDesc('total')
            ->get();

        return view('admin.radiology.analytics.modality-utilization', compact('utilization', 'from', 'to'));
    }

    public function turnaroundTime(Request $request)
    {
        Gate::authorize('radiology.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $examinations = RadiologyExamination::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('completed_at', [$from, $to])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->with(['orderItem.procedure', 'modality'])
            ->get()
            ->map(fn ($exam) => [
                'examination' => $exam,
                'tat_minutes' => $exam->started_at->diffInMinutes($exam->completed_at),
            ]);

        return view('admin.radiology.analytics.turnaround-time', compact('examinations', 'from', 'to'));
    }

    public function criticalFindings(Request $request)
    {
        Gate::authorize('radiology.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $findings = \App\Models\Radiology\RadiologyCriticalFinding::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('detected_at', [$from, $to])
            ->with(['report.examination.orderItem.radiologyOrder.patient'])
            ->latest('detected_at')
            ->paginate(30);

        return view('admin.radiology.analytics.critical-findings', compact('findings', 'from', 'to'));
    }

    private function scopeAndRange(Request $request): array
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return [$companyId, $branchId, $from, $to];
    }
}
