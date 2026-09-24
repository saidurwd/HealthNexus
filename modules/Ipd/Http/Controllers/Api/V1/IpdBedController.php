<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdBedReservationService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdBedController extends Controller
{
    public function __construct(
        private readonly IpdBedReservationService $reservations,
        private readonly IpdBedAllocationService $allocation,
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function reserve(Request $request, IpdBed $bed)
    {
        $this->authorize('reserve', $bed);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'admission_request_id' => ['nullable', 'integer', 'exists:ipd_admission_requests,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = Patient::query()->findOrFail($validated['patient_id']);
        $expiryMinutes = (int) $this->settings->get('ipd.bed_reservation_expiry_minutes', 120);

        try {
            $reservation = $this->reservations->reserve($bed, $patient, $request->user(), $validated['admission_request_id'] ?? null, $validated['reason'] ?? null, $expiryMinutes);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('RESERVE', \App\Models\Ipd\IpdBedReservation::class, $reservation->id, null, $reservation->toArray(), $request);

        return ApiResponse::success($reservation, 'Bed reserved.', 201);
    }

    public function allocate(Request $request, IpdBed $bed)
    {
        $this->authorize('allocate', $bed);

        $validated = $request->validate([
            'admission_id' => ['required', 'integer', 'exists:ipd_admissions,id'],
            'allocation_type' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $admission = IpdAdmission::query()->findOrFail($validated['admission_id']);

        try {
            $allocation = $this->allocation->allocate($admission, $bed, $request->user(), $validated['allocation_type'] ?? 'admission', $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('ALLOCATE', \App\Models\Ipd\IpdBedAllocation::class, $allocation->id, null, $allocation->toArray(), $request);

        return ApiResponse::success($allocation, 'Bed allocated.', 201);
    }

    public function release(Request $request, IpdBed $bed)
    {
        $this->authorize('release', $bed);

        $allocation = $bed->currentAllocation()->firstOrFail();
        $oldValues = $allocation->toArray();

        try {
            $this->allocation->release($allocation, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('RELEASE', \App\Models\Ipd\IpdBedAllocation::class, $allocation->id, $oldValues, $allocation->fresh()->toArray(), $request);

        return ApiResponse::success($allocation->fresh(), 'Bed released.');
    }
}
