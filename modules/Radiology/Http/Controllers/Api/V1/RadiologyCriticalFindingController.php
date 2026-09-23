<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Provider;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyReport;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyCriticalFindingService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ->with(['report.examination.orderItem.radiologyOrder.patient'])
            ->when($request->filled('unacknowledged'), fn ($q) => $q->whereNull('acknowledged_at'))
            ->latest('detected_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($findings);
    }

    public function store(Request $request, RadiologyReport $report)
    {
        $this->authorize('view', $report);

        $validated = $request->validate(['finding_text' => ['required', 'string', 'max:1000']]);

        $detector = Provider::where('user_id', $request->user()->id)->firstOrFail();
        $finding = $this->criticalFindings->flag($report, $validated['finding_text'], $detector);

        $this->auditLogger->log('CRITICAL_FINDING_FLAGGED', RadiologyCriticalFinding::class, $finding->id, null, $finding->toArray(), $request);

        return ApiResponse::success($finding, 'Critical finding flagged and the ordering provider notified.', 201);
    }

    public function acknowledge(Request $request, RadiologyCriticalFinding $finding)
    {
        $this->authorize('acknowledge', $finding);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        try {
            $oldValues = $finding->toArray();
            $this->criticalFindings->acknowledge($finding, $request->user(), $validated['notes'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('ACKNOWLEDGE', RadiologyCriticalFinding::class, $finding->id, $oldValues, $finding->fresh()->toArray(), $request);

        return ApiResponse::success($finding->fresh(), 'Critical finding acknowledged.');
    }
}
