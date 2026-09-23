<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyReport;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyCriticalFindingService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyCriticalFindingController extends Controller
{
    public function __construct(
        private readonly RadiologyCriticalFindingService $criticalFindings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyCriticalFinding::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $findings = RadiologyCriticalFinding::query()
            ->forTenant($companyId, $branchId)
            ->with(['report.examination.orderItem.radiologyOrder.patient', 'notifiedTo'])
            ->when($request->filled('status') && $request->input('status') === 'unacknowledged', fn ($q) => $q->whereNull('acknowledged_at'))
            ->latest('detected_at')
            ->paginate(20);

        return view('admin.radiology.critical-findings.index', compact('findings'));
    }

    public function store(Request $request, RadiologyReport $report)
    {
        $this->authorize('view', $report);

        $validated = $request->validate(['finding_text' => ['required', 'string', 'max:1000']]);

        $detector = Provider::where('user_id', $request->user()->id)->firstOrFail();
        $finding = $this->criticalFindings->flag($report, $validated['finding_text'], $detector);

        $this->auditLogger->log('CRITICAL_FINDING_FLAGGED', RadiologyCriticalFinding::class, $finding->id, null, $finding->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $report)->with('success', 'Critical finding flagged and the ordering provider notified.');
    }

    public function acknowledge(Request $request, RadiologyCriticalFinding $finding)
    {
        $this->authorize('acknowledge', $finding);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);
        $oldValues = $finding->toArray();

        $this->criticalFindings->acknowledge($finding, $request->user(), $validated['notes'] ?? null);

        $this->auditLogger->log('ACKNOWLEDGE', RadiologyCriticalFinding::class, $finding->id, $oldValues, $finding->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.critical-findings.index')->with('success', 'Critical finding acknowledged.');
    }
}
