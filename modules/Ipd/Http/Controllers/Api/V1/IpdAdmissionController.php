<?php

namespace Modules\Ipd\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdAdmissionRequestService;
use App\Services\Ipd\IpdAdmissionService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IpdAdmissionController extends Controller
{
    public function __construct(
        private readonly IpdAdmissionService $admissions,
        private readonly IpdAdmissionRequestService $requests,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdAdmission::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $admissions = IpdAdmission::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'currentAllocation.bed.room.ward'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('admitted_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($admissions);
    }

    public function store(Request $request)
    {
        $this->authorize('create', IpdAdmission::class);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'admission_request_id' => ['nullable', 'integer', 'exists:ipd_admission_requests,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'admission_type_id' => ['nullable', 'integer', 'exists:ipd_admission_types,id'],
            'admission_source_id' => ['nullable', 'integer', 'exists:ipd_admission_sources,id'],
            'admitting_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'attending_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'expected_discharge_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,emergency'],
            'bed_id' => ['nullable', 'integer', 'exists:ipd_beds,id'],
        ]);

        $admissionRequestModel = null;
        if (! empty($validated['admission_request_id'])) {
            $admissionRequestModel = IpdAdmissionRequest::query()->findOrFail($validated['admission_request_id']);
        }
        unset($validated['admission_request_id']);

        try {
            $admission = $this->admissions->admit($validated, $request->user(), $admissionRequestModel);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CREATE', IpdAdmission::class, $admission->id, null, $admission->toArray(), $request);

        return ApiResponse::success($admission, 'Patient admitted.', 201);
    }

    public function show(IpdAdmission $admission)
    {
        $this->authorize('view', $admission);

        $admission->load(['patient', 'encounter', 'admissionType', 'department', 'specialty', 'admittingProvider', 'attendingProvider', 'currentAllocation.bed.room.ward']);

        return ApiResponse::success($admission);
    }

    public function movements(IpdAdmission $admission)
    {
        $this->authorize('view', $admission);

        $movements = $admission->movements()->with(['fromBed', 'toBed'])->latest()->get();

        return ApiResponse::success($movements);
    }

    public function requestsIndex(Request $request)
    {
        Gate::authorize('ipd.admission.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $admissionRequests = IpdAdmissionRequest::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'admissionType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($admissionRequests);
    }

    public function requestsStore(Request $request)
    {
        Gate::authorize('ipd.admission.create');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'source_encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'requesting_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'admission_type_id' => ['nullable', 'integer', 'exists:ipd_admission_types,id'],
            'admission_source_id' => ['nullable', 'integer', 'exists:ipd_admission_sources,id'],
            'reason' => ['nullable', 'string'],
            'provisional_diagnosis' => ['nullable', 'string'],
            'priority' => ['required', 'string', 'in:routine,urgent,emergency'],
            'expected_length_of_stay_days' => ['nullable', 'integer', 'min:0'],
            'expected_admission_date' => ['nullable', 'date'],
            'expected_discharge_date' => ['nullable', 'date'],
            'required_bed_type_id' => ['nullable', 'integer', 'exists:ipd_bed_types,id'],
            'isolation_requirement' => ['nullable', 'string', 'max:100'],
            'special_requirements' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $admissionRequest = $this->requests->create($validated, $request->user());

        $this->auditLogger->log('CREATE', IpdAdmissionRequest::class, $admissionRequest->id, null, $admissionRequest->toArray(), $request);

        return ApiResponse::success($admissionRequest, 'Admission request created.', 201);
    }

    public function requestsShow(IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.view');

        $admissionRequest->load(['patient', 'admissionType', 'admissionSource', 'admission']);

        return ApiResponse::success($admissionRequest);
    }

    public function requestsApprove(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.approve');

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->approve($admissionRequest, $request->user(), $validated['note'] ?? null);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('APPROVE', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return ApiResponse::success($admissionRequest->fresh(), 'Admission request approved.');
    }

    public function requestsReject(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.approve');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->reject($admissionRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('REJECT', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return ApiResponse::success($admissionRequest->fresh(), 'Admission request rejected.');
    }

    public function requestsCancel(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.cancel');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->cancel($admissionRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CANCEL', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return ApiResponse::success($admissionRequest->fresh(), 'Admission request cancelled.');
    }
}
