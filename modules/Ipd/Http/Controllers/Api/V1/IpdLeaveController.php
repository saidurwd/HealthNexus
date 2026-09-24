<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdPatientLeave;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdLeaveService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdLeaveController extends Controller
{
    public function __construct(
        private readonly IpdLeaveService $leaves,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, IpdAdmission $admission)
    {
        $this->authorize('create', IpdPatientLeave::class);

        $validated = $request->validate([
            'leave_type' => ['required', 'string', 'max:50'],
            'expected_return_at' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string'],
            'bed_handling' => ['nullable', 'string', 'in:retain,release'],
        ]);

        $validated['requested_at'] = now();

        $leave = $this->leaves->request($admission, $validated, $request->user());

        $this->auditLogger->log('CREATE', IpdPatientLeave::class, $leave->id, null, $leave->toArray(), $request);

        return ApiResponse::success($leave, 'Leave requested.', 201);
    }

    public function markReturned(Request $request, IpdPatientLeave $leave)
    {
        $this->authorize('complete', $leave);

        $validated = $request->validate(['bed_id' => ['nullable', 'integer', 'exists:ipd_beds,id']]);
        $bed = ! empty($validated['bed_id']) ? IpdBed::query()->find($validated['bed_id']) : null;
        $oldValues = $leave->toArray();

        try {
            $this->leaves->markReturned($leave, $request->user(), $bed);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('RETURN', IpdPatientLeave::class, $leave->id, $oldValues, $leave->fresh()->toArray(), $request);

        return ApiResponse::success($leave->fresh(), 'Patient marked as returned.');
    }
}
