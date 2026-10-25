<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuAdmissionRequest;
use App\Services\AuditLogger;
use App\Services\Icu\IcuAdmissionRequestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuAdmissionRequestController extends Controller
{
    public function __construct(
        private readonly IcuAdmissionRequestService $requests,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IcuAdmissionRequest::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $requests = IcuAdmissionRequest::forTenant($organizationId, $branchId)
            ->with(['patient', 'requestingProvider'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.icu.admission-requests.index', compact('requests'));
    }

    public function create()
    {
        $this->authorize('create', IcuAdmissionRequest::class);

        return view('admin.icu.admission-requests.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', IcuAdmissionRequest::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['required', 'integer', 'exists:encounters,id'],
            'admission_id' => ['nullable', 'integer', 'exists:ipd_admissions,id'],
            'requesting_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'admission_reason' => ['nullable', 'string'],
            'primary_diagnosis_id' => ['nullable', 'integer', 'exists:diagnoses,id'],
            'priority' => ['nullable', 'string'],
            'is_emergency' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string'],
        ]);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        try {
            $icuRequest = $this->requests->create([...$validated, 'organization_id' => $organizationId, 'branch_id' => $branchId], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', IcuAdmissionRequest::class, $icuRequest->id, null, $icuRequest->toArray(), $request);

        return redirect()->route('admin.icu.admission-requests.show', $icuRequest)->with('success', __('ICU admission request submitted.'));
    }

    public function show(IcuAdmissionRequest $icuRequest)
    {
        $this->authorize('view', $icuRequest);

        return view('admin.icu.admission-requests.show', ['icuRequest' => $icuRequest->load(['patient', 'requestingProvider', 'episode'])]);
    }

    public function approve(Request $request, IcuAdmissionRequest $icuRequest)
    {
        $this->authorize('approve', $icuRequest);

        try {
            $icuRequest = $this->requests->accept($icuRequest, $request->user(), $request->input('note'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.admission-requests.show', $icuRequest)->with('success', __('ICU admission request accepted.'));
    }

    public function reject(Request $request, IcuAdmissionRequest $icuRequest)
    {
        $this->authorize('reject', $icuRequest);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $icuRequest = $this->requests->reject($icuRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.admission-requests.show', $icuRequest)->with('success', __('ICU admission request rejected.'));
    }

    public function cancel(Request $request, IcuAdmissionRequest $icuRequest)
    {
        $this->authorize('cancel', $icuRequest);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $icuRequest = $this->requests->cancel($icuRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.admission-requests.show', $icuRequest)->with('success', __('ICU admission request cancelled.'));
    }
}
