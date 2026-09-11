<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Requests\Api\UpdatePatientRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $patients = Patient::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->when($request->filled('search'), fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('enterprise_patient_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->get();

        return ApiResponse::success($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        abort_if(
            ! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $validated['company_id'])->exists(),
            403,
            'You do not have access to this company.'
        );

        $patient = Patient::create($validated);

        $this->auditLogger->log('CREATE', Patient::class, $patient->id, null, $patient->toArray(), $request);

        return ApiResponse::success($patient, 'Patient created successfully', 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        return ApiResponse::success($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $validated = $request->validated();

        $patient->update($validated);

        $this->auditLogger->log('UPDATE', Patient::class, $patient->id, $patient->getOriginal(), $patient->toArray(), $request);

        return ApiResponse::success($patient, 'Patient updated successfully');
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        $oldValues = $patient->toArray();

        $patient->delete();

        $this->auditLogger->log('DELETE', Patient::class, $patient->id, $oldValues, null, $request);

        return ApiResponse::success(null, 'Patient deleted successfully');
    }
}
