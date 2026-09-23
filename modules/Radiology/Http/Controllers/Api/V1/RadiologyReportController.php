<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyReport;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RadiologyReportController extends Controller
{
    public function __construct(
        private readonly RadiologyReportService $reports,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyReport::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $reports = RadiologyReport::query()
            ->forTenant($companyId, $branchId)
            ->where('is_current', true)
            ->with('examination.orderItem.radiologyOrder.patient')
            ->latest('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($reports);
    }

    public function show(RadiologyReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['examination.orderItem.radiologyOrder.patient', 'radiologist', 'criticalFindings']);

        return ApiResponse::success($report);
    }

    public function store(Request $request, RadiologyExamination $examination)
    {
        $this->authorize('create', RadiologyReport::class);

        $validated = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:radiology_report_templates,id'],
            'clinical_indication' => ['nullable', 'string'],
            'technique' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ]);

        $radiologist = Provider::where('user_id', $request->user()->id)->firstOrFail();

        try {
            $report = $this->reports->createDraft($examination, $radiologist, $validated);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CREATE', RadiologyReport::class, $report->id, null, $report->toArray(), $request);

        return ApiResponse::success($report, 'Report draft created.', 201);
    }

    public function submit(Request $request, RadiologyReport $report)
    {
        $this->authorize('submit', $report);

        try {
            $oldValues = $report->toArray();
            $this->reports->submit($report);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('SUBMIT', RadiologyReport::class, $report->id, $oldValues, $report->fresh()->toArray(), $request);

        return ApiResponse::success($report->fresh(), 'Report submitted for approval.');
    }

    public function approve(Request $request, RadiologyReport $report)
    {
        $this->authorize('approve', $report);

        $approver = Provider::where('user_id', $request->user()->id)->firstOrFail();

        try {
            $oldValues = $report->toArray();
            $this->reports->approve($report, $approver);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('APPROVE', RadiologyReport::class, $report->id, $oldValues, $report->fresh()->toArray(), $request);

        return ApiResponse::success($report->fresh(), 'Report approved and finalized.');
    }

    public function amend(Request $request, RadiologyReport $report)
    {
        $this->authorize('amend', $report);

        $validated = $request->validate([
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $amended = $this->reports->amend($report, $validated, $validated['reason'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('AMEND', RadiologyReport::class, $amended->id, $report->toArray(), $amended->toArray(), $request);

        return ApiResponse::success($amended, 'Report amended.', 201);
    }
}
