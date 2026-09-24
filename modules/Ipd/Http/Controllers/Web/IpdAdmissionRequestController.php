<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdAdmissionSource;
use App\Models\Ipd\IpdAdmissionType;
use App\Models\Ipd\IpdBedType;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdAdmissionRequestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class IpdAdmissionRequestController extends Controller
{
    public function __construct(
        private readonly IpdAdmissionRequestService $requests,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('ipd.admission.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $admissionRequests = IpdAdmissionRequest::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'admissionType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.admission-requests.index', compact('admissionRequests'));
    }

    public function create(Request $request)
    {
        Gate::authorize('ipd.admission.create');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $admissionTypes = IpdAdmissionType::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $admissionSources = IpdAdmissionSource::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $bedTypes = IpdBedType::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $patient = $request->filled('patient_id') ? Patient::find($request->input('patient_id')) : null;

        return view('admin.ipd.admission-requests.create', compact('admissionTypes', 'admissionSources', 'bedTypes', 'patient'));
    }

    public function store(Request $request)
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

        return redirect()->route('admin.ipd.admission-requests.show', $admissionRequest)->with('success', 'Admission request created.');
    }

    public function show(IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.view');

        $admissionRequest->load(['patient', 'admissionType', 'admissionSource', 'department', 'specialty', 'requestingProvider', 'requestedBy', 'approvedBy', 'admission']);

        return view('admin.ipd.admission-requests.show', compact('admissionRequest'));
    }

    public function approve(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.approve');

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->approve($admissionRequest, $request->user(), $validated['note'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('APPROVE', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.admission-requests.show', $admissionRequest)->with('success', 'Admission request approved.');
    }

    public function reject(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.approve');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->reject($admissionRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('REJECT', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.admission-requests.show', $admissionRequest)->with('success', 'Admission request rejected.');
    }

    public function cancel(Request $request, IpdAdmissionRequest $admissionRequest)
    {
        Gate::authorize('ipd.admission.cancel');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $admissionRequest->toArray();

        try {
            $this->requests->cancel($admissionRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CANCEL', IpdAdmissionRequest::class, $admissionRequest->id, $oldValues, $admissionRequest->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.admission-requests.index')->with('success', 'Admission request cancelled.');
    }
}
