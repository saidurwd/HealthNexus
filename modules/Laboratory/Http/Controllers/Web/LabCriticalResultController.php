<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabCriticalResultAlert;
use App\Services\AuditLogger;
use App\Services\Laboratory\CriticalResultService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class LabCriticalResultController extends Controller
{
    public function __construct(
        private readonly CriticalResultService $criticalResults,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabCriticalResultAlert::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $alerts = LabCriticalResultAlert::query()
            ->forTenant($companyId, $branchId)
            ->with(['result.test', 'result.orderItem.labOrder.patient', 'notifiedTo'])
            ->when($request->filled('status') && $request->input('status') === 'unacknowledged', fn ($q) => $q->whereNull('acknowledged_at'))
            ->latest('detected_at')
            ->paginate(20);

        return view('admin.lab.critical-results.index', compact('alerts'));
    }

    public function acknowledge(Request $request, LabCriticalResultAlert $alert)
    {
        $this->authorize('acknowledge', $alert);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);
        $oldValues = $alert->toArray();

        $this->criticalResults->acknowledge($alert, $request->user(), $validated['notes'] ?? null);

        $this->auditLogger->log('ACKNOWLEDGE', LabCriticalResultAlert::class, $alert->id, $oldValues, $alert->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.critical-results.index')->with('success', 'Critical result acknowledged.');
    }
}
