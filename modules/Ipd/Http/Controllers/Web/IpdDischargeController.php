<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdDischargeDisposition;
use App\Models\Ipd\IpdDischargeRequest;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdDischargeService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdDischargeController extends Controller
{
    public function __construct(
        private readonly IpdDischargeService $discharge,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdDischargeRequest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $dischargeRequests = IpdDischargeRequest::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'admission'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.discharge.index', compact('dischargeRequests'));
    }

    public function create(IpdAdmission $admission)
    {
        $this->authorize('create', IpdDischargeRequest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $dispositions = IpdDischargeDisposition::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.discharge.create', compact('admission', 'dispositions'));
    }

    public function store(Request $request, IpdAdmission $admission)
    {
        $this->authorize('create', IpdDischargeRequest::class);

        $validated = $request->validate([
            'discharge_type' => ['required', 'string', 'max:50'],
            'planned_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'discharge_diagnosis' => ['nullable', 'string'],
            'disposition_id' => ['nullable', 'integer', 'exists:ipd_discharge_dispositions,id'],
            'instructions' => ['nullable', 'string'],
            'follow_up_required' => ['boolean'],
            'follow_up_provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $dischargeRequest = $this->discharge->request($admission, $validated, $request->user());

        $this->auditLogger->log('CREATE', IpdDischargeRequest::class, $dischargeRequest->id, null, $dischargeRequest->toArray(), $request);

        return redirect()->route('admin.ipd.discharge.show', $dischargeRequest)->with('success', 'Discharge requested.');
    }

    public function show(IpdDischargeRequest $dischargeRequest)
    {
        $this->authorize('view', $dischargeRequest);

        $dischargeRequest->load(['patient', 'admission.currentAllocation.bed.room.ward', 'disposition', 'followUpProvider']);

        return view('admin.ipd.discharge.show', compact('dischargeRequest'));
    }

    public function recordClearance(Request $request, IpdDischargeRequest $dischargeRequest)
    {
        $this->authorize('approve', $dischargeRequest);

        $validated = $request->validate(['type' => ['required', 'string', 'in:clinical,billing,pharmacy']]);
        $oldValues = $dischargeRequest->toArray();

        try {
            $this->discharge->recordClearance($dischargeRequest, $validated['type'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('RECORD_CLEARANCE', IpdDischargeRequest::class, $dischargeRequest->id, $oldValues, $dischargeRequest->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.discharge.show', $dischargeRequest)->with('success', 'Clearance recorded.');
    }

    public function complete(Request $request, IpdDischargeRequest $dischargeRequest)
    {
        $this->authorize('complete', $dischargeRequest);

        $oldValues = $dischargeRequest->toArray();

        try {
            $this->discharge->complete($dischargeRequest, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', IpdDischargeRequest::class, $dischargeRequest->id, $oldValues, $dischargeRequest->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.discharge.show', $dischargeRequest)->with('success', 'Discharge completed.');
    }
}
