<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgeryRequest;
use App\Services\AuditLogger;
use App\Services\Ot\OtSurgeryRequestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtSurgeryRequestController extends Controller
{
    public function __construct(
        private readonly OtSurgeryRequestService $requests,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', OtSurgeryRequest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $requests = OtSurgeryRequest::forTenant($companyId, $branchId)
            ->with(['patient', 'procedure', 'requestingProvider'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ot.surgery-requests.index', compact('requests'));
    }

    public function create()
    {
        $this->authorize('create', OtSurgeryRequest::class);

        return view('admin.ot.surgery-requests.create');
    }

    public function show(OtSurgeryRequest $surgeryRequest)
    {
        $this->authorize('view', $surgeryRequest);

        $surgeryRequest->load(['patient', 'encounter', 'procedure', 'specialty', 'requestingProvider', 'surgery']);

        return view('admin.ot.surgery-requests.show', compact('surgeryRequest'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', OtSurgeryRequest::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['required', 'integer', 'exists:encounters,id'],
            'admission_id' => ['nullable', 'integer', 'exists:ipd_admissions,id'],
            'requesting_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'procedure_id' => ['nullable', 'integer', 'exists:ot_procedures,id'],
            'indication' => ['nullable', 'string'],
            'site' => ['nullable', 'string', 'max:255'],
            'laterality' => ['nullable', 'string', 'max:50'],
            'diagnosis_id' => ['nullable', 'integer', 'exists:diagnoses,id'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,emergency'],
            'surgery_type' => ['nullable', 'string', 'in:elective,emergency'],
            'preferred_date' => ['nullable', 'date'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'implant_requirement' => ['nullable', 'boolean'],
            'blood_requirement' => ['nullable', 'boolean'],
            'specimen_requirement' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $surgeryRequest = $this->requests->create($validated, $request->user());

        $this->auditLogger->log('CREATE', OtSurgeryRequest::class, $surgeryRequest->id, null, $surgeryRequest->toArray(), $request);

        return redirect()->route('admin.ot.surgery-requests.show', $surgeryRequest)->with('success', 'Surgery request created.');
    }

    public function approve(Request $request, OtSurgeryRequest $surgeryRequest)
    {
        $this->authorize('approve', $surgeryRequest);

        try {
            $this->requests->approve($surgeryRequest, $request->user(), $request->input('note'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('APPROVE', OtSurgeryRequest::class, $surgeryRequest->id, null, ['status' => 'approved'], $request);

        return back()->with('success', 'Surgery request approved.');
    }

    public function reject(Request $request, OtSurgeryRequest $surgeryRequest)
    {
        $this->authorize('approve', $surgeryRequest);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->requests->reject($surgeryRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('REJECT', OtSurgeryRequest::class, $surgeryRequest->id, null, $validated, $request);

        return back()->with('success', 'Surgery request rejected.');
    }

    public function cancel(Request $request, OtSurgeryRequest $surgeryRequest)
    {
        $this->authorize('cancel', $surgeryRequest);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->requests->cancel($surgeryRequest, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CANCEL', OtSurgeryRequest::class, $surgeryRequest->id, null, $validated, $request);

        return back()->with('success', 'Surgery request cancelled.');
    }
}
