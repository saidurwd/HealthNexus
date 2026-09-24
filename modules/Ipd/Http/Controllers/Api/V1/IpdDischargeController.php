<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdDischargeRequest;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdDischargeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdDischargeController extends Controller
{
    public function __construct(
        private readonly IpdDischargeService $discharge,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, IpdAdmission $admission)
    {
        $this->authorize('create', IpdDischargeRequest::class);

        $validated = $request->validate([
            'discharge_type' => ['required', 'string', 'max:50'],
            'planned_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'discharge_diagnosis' => ['nullable', 'string'],
            'disposition_id' => ['nullable', 'integer', 'exists:ipd_discharge_dispositions,id'],
            'instructions' => ['nullable', 'string'],
            'follow_up_required' => ['boolean'],
            'follow_up_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $dischargeRequest = $this->discharge->request($admission, $validated, $request->user());

        $this->auditLogger->log('CREATE', IpdDischargeRequest::class, $dischargeRequest->id, null, $dischargeRequest->toArray(), $request);

        return ApiResponse::success($dischargeRequest, 'Discharge requested.', 201);
    }

    public function clearance(Request $request, IpdDischargeRequest $dischargeRequest)
    {
        $this->authorize('approve', $dischargeRequest);

        $validated = $request->validate(['type' => ['required', 'string', 'in:clinical,billing,pharmacy']]);
        $oldValues = $dischargeRequest->toArray();

        try {
            $this->discharge->recordClearance($dischargeRequest, $validated['type'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('RECORD_CLEARANCE', IpdDischargeRequest::class, $dischargeRequest->id, $oldValues, $dischargeRequest->fresh()->toArray(), $request);

        return ApiResponse::success($dischargeRequest->fresh(), 'Clearance recorded.');
    }

    public function complete(Request $request, IpdDischargeRequest $dischargeRequest)
    {
        $this->authorize('complete', $dischargeRequest);

        $oldValues = $dischargeRequest->toArray();

        try {
            $this->discharge->complete($dischargeRequest, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('COMPLETE', IpdDischargeRequest::class, $dischargeRequest->id, $oldValues, $dischargeRequest->fresh()->toArray(), $request);

        return ApiResponse::success($dischargeRequest->fresh(), 'Discharge completed.');
    }
}
