<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Radiology\RadiologyExamination;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyExaminationController extends Controller
{
    public function __construct(
        private readonly RadiologyExaminationService $examinations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyExamination::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $examinations = RadiologyExamination::query()
            ->forTenant($companyId, $branchId)
            ->with(['orderItem.radiologyOrder.patient', 'orderItem.procedure', 'modality'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('scheduled_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($examinations);
    }

    public function show(RadiologyExamination $examination)
    {
        $this->authorize('view', $examination);

        $examination->load(['orderItem.radiologyOrder.patient', 'orderItem.procedure', 'modality', 'technologist', 'studies']);

        return ApiResponse::success($examination);
    }

    public function start(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('start', $examination);

        $oldValues = $examination->toArray();
        $this->examinations->start($examination, $request->user());

        $this->auditLogger->log('START', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return ApiResponse::success($examination->fresh(), 'Examination started.');
    }

    public function complete(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('complete', $examination);

        $validated = $request->validate(['technical_notes' => ['nullable', 'string']]);
        $oldValues = $examination->toArray();

        $this->examinations->complete($examination, $request->user(), $validated);

        $this->auditLogger->log('COMPLETE', RadiologyExamination::class, $examination->id, $oldValues, $examination->fresh()->toArray(), $request);

        return ApiResponse::success($examination->fresh(), 'Examination completed.');
    }
}
