<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabResult;
use App\Models\Laboratory\LabSpecimen;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabReportingController extends Controller
{
    public function dailyVolume(Request $request)
    {
        Gate::authorize('lab.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $volume = LabOrder::query()
            ->forTenant($companyId, $branchId)
            ->whereBetween('ordered_at', [$from, $to])
            ->selectRaw('DATE(ordered_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.lab.reports.daily-volume', compact('volume', 'from', 'to'));
    }

    public function sampleRejection(Request $request)
    {
        Gate::authorize('lab.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $rejections = LabSpecimen::query()
            ->forTenant($companyId, $branchId)
            ->where('status', 'rejected')
            ->whereBetween('rejected_at', [$from, $to])
            ->selectRaw('rejection_reason, count(*) as total')
            ->groupBy('rejection_reason')
            ->orderByDesc('total')
            ->get();

        return view('admin.lab.reports.sample-rejection', compact('rejections', 'from', 'to'));
    }

    public function criticalResults(Request $request)
    {
        Gate::authorize('lab.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $results = LabResult::query()
            ->forTenant($companyId, $branchId)
            ->where('critical_flag', true)
            ->whereBetween('entered_at', [$from, $to])
            ->with(['test', 'orderItem.labOrder.patient'])
            ->latest('entered_at')
            ->paginate(30);

        return view('admin.lab.reports.critical-results', compact('results', 'from', 'to'));
    }

    public function resultAmendments(Request $request)
    {
        Gate::authorize('lab.report.view');

        [$companyId, $branchId, $from, $to] = $this->scopeAndRange($request);

        $amendments = LabResult::query()
            ->forTenant($companyId, $branchId)
            ->whereNotNull('amended_from_id')
            ->whereBetween('entered_at', [$from, $to])
            ->with(['test', 'amendedBy', 'orderItem.labOrder.patient'])
            ->latest('entered_at')
            ->paginate(30);

        return view('admin.lab.reports.result-amendments', compact('amendments', 'from', 'to'));
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
