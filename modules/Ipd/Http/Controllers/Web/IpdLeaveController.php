<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdPatientLeave;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdLeaveService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdLeaveController extends Controller
{
    public function __construct(
        private readonly IpdLeaveService $leaves,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdPatientLeave::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $leaves = IpdPatientLeave::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'admission'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.leave.index', compact('leaves'));
    }

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

        return redirect()->route('admin.ipd.leave.show', $leave)->with('success', 'Leave requested.');
    }

    public function show(IpdPatientLeave $leave)
    {
        $this->authorize('view', $leave);

        $leave->load(['patient', 'admission.currentAllocation.bed.room.ward', 'requestedBy', 'approvedBy']);

        return view('admin.ipd.leave.show', compact('leave'));
    }

    public function approve(Request $request, IpdPatientLeave $leave)
    {
        $this->authorize('approve', $leave);

        $oldValues = $leave->toArray();

        try {
            $this->leaves->approve($leave, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('APPROVE', IpdPatientLeave::class, $leave->id, $oldValues, $leave->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.leave.show', $leave)->with('success', 'Leave approved.');
    }

    public function start(Request $request, IpdPatientLeave $leave)
    {
        $this->authorize('approve', $leave);

        $oldValues = $leave->toArray();

        try {
            $this->leaves->start($leave, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('START', IpdPatientLeave::class, $leave->id, $oldValues, $leave->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.leave.show', $leave)->with('success', 'Leave started.');
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
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('RETURN', IpdPatientLeave::class, $leave->id, $oldValues, $leave->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.leave.show', $leave)->with('success', 'Patient marked as returned.');
    }

    public function cancel(Request $request, IpdPatientLeave $leave)
    {
        $this->authorize('approve', $leave);

        $oldValues = $leave->toArray();

        try {
            $this->leaves->cancel($leave, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CANCEL', IpdPatientLeave::class, $leave->id, $oldValues, $leave->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.leave.index')->with('success', 'Leave cancelled.');
    }
}
