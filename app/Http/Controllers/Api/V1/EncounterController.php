<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEncounterRequest;
use App\Http\Requests\Api\UpdateEncounterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Encounter;
use App\Models\EncounterAmendment;
use App\Models\Prescription;
use App\Services\AuditLogger;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\EncounterLifecycleService;
use App\Services\EncounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncounterController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
        private EncounterService $encounterService,
        private EncounterLifecycleService $lifecycleService,
        private EncounterClinicalService $clinicalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $encounters = Encounter::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->when($request->filled('branch_id'), fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->filled('patient_id'), fn ($q, $patientId) => $q->where('patient_id', $patientId))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('type'), fn ($q, $type) => $q->where('encounter_type', $type))
            ->with(['patient', 'provider', 'encounterType'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($encounters);
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

        if (isset($validated['status'])) {
            $this->lifecycleService->moveTo($encounter, $validated['status'], $request->user());
            unset($validated['status']);
        }

        if (! empty($validated)) {
            $encounter->update($validated);
        }

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

    public function start(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->start($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter started successfully');
    }

    public function pause(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->pause($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter paused successfully');
    }

    public function resume(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->resume($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter resumed successfully');
    }

    public function complete(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->complete($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter completed successfully');
    }

    public function lock(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->lock($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter locked successfully');
    }

    public function cancel(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->cancel($encounter, null, $request->user());

        return ApiResponse::success($encounter, 'Encounter cancelled successfully');
    }

    public function transfer(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        $this->lifecycleService->transfer($encounter, $request->user());

        return ApiResponse::success($encounter, 'Encounter transferred successfully');
    }

    public function storeVital(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('manageVitals', $encounter);

        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'temperature_unit' => ['nullable', 'in:celsius,fahrenheit'],
            'systolic' => ['nullable', 'integer', 'min:50', 'max:300'],
            'diastolic' => ['nullable', 'integer', 'min:30', 'max:200'],
            'bp_unit' => ['nullable', 'in:mmhg,atm,kpa'],
            'pulse_rate' => ['nullable', 'integer', 'min:30', 'max:250'],
            'respiratory_rate' => ['nullable', 'integer', 'min:8', 'max:50'],
            'height' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'oxygen_saturation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $vital = $this->clinicalService->addVital($encounter, $validated, $request->user());

        $this->auditLogger->log('CREATE', $vital::class, $vital->id, null, $vital->toArray(), $request);

        return ApiResponse::success($vital, 'Vital signs recorded successfully', 201);
    }

    public function vitals(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success($encounter->vitals()->latest('recorded_at')->get());
    }

    public function storeDiagnosis(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('manageDiagnosis', $encounter);

        $validated = $request->validate([
            'code_type' => ['nullable', 'string', 'max:50'],
            'coding_system' => ['nullable', 'string', 'max:50', 'in:ICD-10,ICD-11,SNOMED-CT,Other'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'status' => ['nullable', 'in:provisional,confirmed,rule_out,resolved'],
            'diagnosis_type' => ['nullable', 'in:primary,secondary,differential,historical'],
            'is_primary' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $diagnosis = $this->clinicalService->addDiagnosis($encounter, $validated, $request->user());

        $this->auditLogger->log('CREATE', $diagnosis::class, $diagnosis->id, null, $diagnosis->toArray(), $request);

        return ApiResponse::success($diagnosis, 'Diagnosis added successfully', 201);
    }

    public function diagnoses(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success($encounter->diagnoses()->latest('recorded_at')->get());
    }

    public function storeOrder(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('manageOrder', $encounter);

        $validated = $request->validate([
            'order_type' => ['required', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $this->clinicalService->createOrder($encounter, $validated, $request->user());

        $this->auditLogger->log('CREATE', $order::class, $order->id, null, $order->toArray(), $request);

        return ApiResponse::success($order, 'Clinical order created successfully', 201);
    }

    public function orders(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success($encounter->orders()->with('items')->latest()->get());
    }

    public function storePrescription(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('createPrescription', $encounter);

        $validated = $request->validate([
            'clinical_notes' => ['nullable', 'string'],
            'advice' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string', 'max:255'],
            'items.*.dosage_form' => ['nullable', 'string', 'max:50'],
            'items.*.strength' => ['nullable', 'string', 'max:50'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $prescription = $this->clinicalService->createPrescription($encounter, $validated, $request->user());

        $this->auditLogger->log('CREATE', $prescription::class, $prescription->id, null, $prescription->toArray(), $request);

        return ApiResponse::success($prescription->load('items'), 'Prescription created successfully', 201);
    }

    public function prescriptions(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success($encounter->prescriptions()->with('items')->latest()->get());
    }

    public function issuePrescription(Request $request, Encounter $encounter, Prescription $prescription): JsonResponse
    {
        $this->authorize('issuePrescription', $encounter);

        $prescription = $this->clinicalService->issuePrescription($encounter, $prescription, $request->user());

        $this->auditLogger->log('UPDATE', $prescription::class, $prescription->id, null, ['status' => $prescription->status], $request);

        return ApiResponse::success($prescription, 'Prescription issued successfully');
    }

    public function cancelPrescription(Request $request, Encounter $encounter, Prescription $prescription): JsonResponse
    {
        $this->authorize('cancelPrescription', $encounter);

        $prescription = $this->clinicalService->cancelPrescription($encounter, $prescription, $request->user());

        $this->auditLogger->log('UPDATE', $prescription::class, $prescription->id, null, ['status' => $prescription->status], $request);

        return ApiResponse::success($prescription, 'Prescription cancelled successfully');
    }

    public function storeAmendment(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('requestAmendment', $encounter);

        $validated = $request->validate([
            'amendment_type' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $amendment = $this->clinicalService->createAmendment($encounter, $validated, $request->user());

        $this->auditLogger->log('CREATE', $amendment::class, $amendment->id, null, $amendment->toArray(), $request);

        return ApiResponse::success($amendment, 'Amendment requested successfully', 201);
    }

    public function approveAmendment(Request $request, Encounter $encounter, EncounterAmendment $amendment): JsonResponse
    {
        abort_unless($amendment->encounter_id === $encounter->id, 404);

        $this->authorize('approveAmendment', $encounter);

        $amendment = $this->clinicalService->approveAmendment($amendment, $request->user());

        $this->auditLogger->log('UPDATE', $amendment::class, $amendment->id, null, ['status' => 'approved'], $request);

        return ApiResponse::success($amendment, 'Amendment approved successfully');
    }

    public function summary(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        $encounter->load([
            'complaints',
            'histories',
            'examinations',
            'reviewOfSystems',
            'vitals',
            'diagnoses',
            'problems',
            'procedures',
            'orders.items',
            'prescriptions.items',
            'referrals',
            'instructions',
            'notes',
            'documents',
            'amendments',
        ]);

        return ApiResponse::success([
            'encounter' => $encounter,
            'summary' => [
                'complaints' => $encounter->complaints,
                'histories' => $encounter->histories,
                'examinations' => $encounter->examinations,
                'vitals' => $encounter->vitals,
                'diagnoses' => $encounter->diagnoses,
                'problems' => $encounter->problems,
                'procedures' => $encounter->procedures,
                'orders' => $encounter->orders,
                'prescriptions' => $encounter->prescriptions,
                'referrals' => $encounter->referrals,
                'instructions' => $encounter->instructions,
                'documents' => $encounter->documents,
                'amendments' => $encounter->amendments,
            ],
        ]);
    }
}
