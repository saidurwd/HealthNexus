<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEncounterRequest;
use App\Http\Requests\Api\UpdateEncounterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Encounter;
use App\Services\AuditLogger;
use App\Services\EncounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncounterController extends Controller
{
    public function __construct(private AuditLogger $auditLogger, private EncounterService $encounterService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $encounters = Encounter::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->when($request->filled('patient_id'), fn ($q, $patientId) => $q->where('patient_id', $patientId))
            ->when($request->filled('type'), fn ($q, $type) => $q->where('encounter_type', $type))
            ->get();

        return ApiResponse::success($encounters);
    }

    public function store(StoreEncounterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        abort_if(
            ! $user->hasRole('super_admin') && ! $user->companies()->where('companies.id', $validated['company_id'])->exists(),
            403,
            'You do not have access to this company.'
        );

        $encounter = $this->encounterService->createEncounter($validated, $user);

        $this->auditLogger->log('CREATE', Encounter::class, $encounter->id, null, $encounter->toArray(), $request);

        return ApiResponse::success($encounter, 'Encounter created successfully', 201);
    }

    public function show(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success($encounter);
    }

    public function update(UpdateEncounterRequest $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $validated = $request->validated();

        $encounter->update($validated);

        $this->auditLogger->log('UPDATE', Encounter::class, $encounter->id, $encounter->getOriginal(), $encounter->toArray(), $request);

        return ApiResponse::success($encounter, 'Encounter updated successfully');
    }

    public function destroy(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('delete', $encounter);

        $oldValues = $encounter->toArray();

        $encounter->delete();

        $this->auditLogger->log('DELETE', Encounter::class, $encounter->id, $oldValues, null, $request);

        return ApiResponse::success(null, 'Encounter deleted successfully');
    }
}
