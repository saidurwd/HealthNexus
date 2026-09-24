<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdTransferController extends Controller
{
    public function __construct(
        private readonly IpdTransferService $transfers,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, IpdAdmission $admission)
    {
        $this->authorize('create', IpdBedMovement::class);

        $validated = $request->validate([
            'bed_id' => ['required', 'integer', 'exists:ipd_beds,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $bed = IpdBed::query()->findOrFail($validated['bed_id']);

        try {
            $movement = $this->transfers->request($admission, $bed, $request->user(), IpdBedMovement::TYPE_TRANSFER, $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('REQUEST', IpdBedMovement::class, $movement->id, null, $movement->toArray(), $request);

        return ApiResponse::success($movement, 'Transfer requested.', 201);
    }

    public function approve(Request $request, IpdBedMovement $transfer)
    {
        $this->authorize('approve', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->approve($transfer, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('APPROVE', IpdBedMovement::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return ApiResponse::success($transfer->fresh(), 'Transfer approved.');
    }

    public function complete(Request $request, IpdBedMovement $transfer)
    {
        $this->authorize('complete', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->complete($transfer, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('COMPLETE', IpdBedMovement::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return ApiResponse::success($transfer->fresh(), 'Transfer completed.');
    }
}
