<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Services\AuditLogger;
use App\Services\Laboratory\LabReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class LabReportController extends Controller
{
    public function __construct(
        private readonly LabReportService $reports,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabReport::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $reports = LabReport::query()
            ->forTenant($companyId, $branchId)
            ->with('labOrder.patient')
            ->when($request->filled('search'), fn ($q) => $q->where('report_number', 'like', '%'.$request->input('search').'%'))
            ->latest('generated_at')
            ->paginate(20);

        return view('admin.lab.reports.index', compact('reports'));
    }

    public function show(LabReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['labOrder.patient', 'labOrder.encounter', 'labOrder.items.test', 'generatedBy']);

        return view('admin.lab.reports.show', compact('report'));
    }

    public function print(LabReport $report)
    {
        $this->authorize('print', $report);

        $report->load(['labOrder.patient', 'labOrder.encounter.provider', 'labOrder.items.test.section']);

        return view('admin.lab.reports.print', compact('report'));
    }

    public function finalize(Request $request, LabOrder $order)
    {
        $this->authorize('generate', LabReport::class);

        $report = $this->reports->finalize($order, $request->user());

        $this->auditLogger->log('FINALIZE', LabReport::class, $report->id, null, $report->toArray(), $request);

        return redirect()->route('admin.lab.reports.show', $report)->with('success', 'Report finalized: '.$report->report_number);
    }
}
