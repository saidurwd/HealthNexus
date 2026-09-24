<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdBed;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdAdmissionService;
use App\Services\Ipd\IpdBedAvailabilityService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdAdmissionController extends Controller
{
    public function __construct(
        private readonly IpdAdmissionService $admissions,
        private readonly IpdBedAvailabilityService $availability,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdAdmission::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $admissions = IpdAdmission::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'currentAllocation.bed.room.ward', 'attendingProvider'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('current_only'), fn ($q) => $q->whereIn('status', ['admitted', 'active', 'transfer_requested', 'transferred', 'discharge_planned', 'discharge_pending']))
            ->when($request->filled('search'), fn ($q) => $q->where('admission_number', 'like', '%'.$request->input('search').'%'))
            ->latest('admitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.admissions.index', compact('admissions'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', IpdAdmission::class);

        $admissionRequest = $request->filled('admission_request_id')
            ? IpdAdmissionRequest::query()->where('status', IpdAdmissionRequest::STATUS_APPROVED)->findOrFail($request->input('admission_request_id'))
            : null;

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $availableBeds = $this->availability->search($companyId, $branchId, ['status' => IpdBed::STATUS_AVAILABLE], 100);

        return view('admin.ipd.admissions.create', compact('admissionRequest', 'availableBeds'));
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
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', IpdAdmission::class, $admission->id, null, $admission->toArray(), $request);

        return redirect()->route('admin.ipd.admissions.show', $admission)->with('success', 'Patient admitted.');
    }

    public function show(IpdAdmission $admission)
    {
        $this->authorize('view', $admission);

        $admission->load([
            'patient', 'encounter', 'admissionRequest', 'admissionType', 'admissionSource',
            'department', 'specialty', 'admittingProvider', 'attendingProvider',
            'dischargeDisposition', 'currentAllocation.bed.room.ward',
            'providerAssignments.provider', 'movements.fromBed', 'movements.toBed',
            'leaves', 'dischargeRequests',
        ]);

        return view('admin.ipd.admissions.show', compact('admission'));
    }
}
