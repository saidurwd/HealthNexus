<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdBedAvailabilityService;
use App\Services\Ipd\IpdTransferService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdTransferController extends Controller
{
    public function __construct(
        private readonly IpdTransferService $transfers,
        private readonly IpdBedAvailabilityService $availability,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdBedMovement::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $transfers = IpdBedMovement::query()
            ->forTenant($companyId, $branchId)
            ->where('movement_type', IpdBedMovement::TYPE_TRANSFER)
            ->with(['patient', 'admission', 'fromBed', 'toBed'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ipd.transfers.index', compact('transfers'));
    }

    public function create(IpdAdmission $admission)
    {
        $this->authorize('create', IpdBedMovement::class);

        $admission->load(['patient', 'currentAllocation.bed.room.ward']);
        $availableBeds = $this->availability->search($admission->company_id, $admission->branch_id, ['status' => IpdBed::STATUS_AVAILABLE], 100);

        return view('admin.ipd.transfers.create', compact('admission', 'availableBeds'));
    }

    public function store(Request $request, IpdAdmission $admission)
    {
        $this->authorize('create', IpdBedMovement::class);

        $validated = $request->validate([
            'bed_id' => ['required', 'integer', 'exists:ipd_beds,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $bed = IpdBed::query()->findOrFail($validated['bed_id']);

        try {
            $movement = $this->transfers->request($admission, $bed, $request->user(), IpdBedMovement::TYPE_TRANSFER, $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('REQUEST', IpdBedMovement::class, $movement->id, null, $movement->toArray(), $request);

        return redirect()->route('admin.ipd.transfers.show', $movement)->with('success', 'Transfer requested.');
    }

    public function show(IpdBedMovement $transfer)
    {
        $this->authorize('view', $transfer);

        $transfer->load(['patient', 'admission', 'fromBed.room.ward', 'toBed.room.ward', 'requestedBy', 'approvedBy', 'completedBy']);

        return view('admin.ipd.transfers.show', compact('transfer'));
    }

    public function approve(Request $request, IpdBedMovement $transfer)
    {
        $this->authorize('approve', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->approve($transfer, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('APPROVE', IpdBedMovement::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.transfers.show', $transfer)->with('success', 'Transfer approved.');
    }

    public function complete(Request $request, IpdBedMovement $transfer)
    {
        $this->authorize('complete', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->complete($transfer, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', IpdBedMovement::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.transfers.show', $transfer)->with('success', 'Transfer completed.');
    }

    public function cancel(Request $request, IpdBedMovement $transfer)
    {
        $this->authorize('cancel', $transfer);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $oldValues = $transfer->toArray();

        try {
            $this->transfers->cancel($transfer, $request->user(), $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CANCEL', IpdBedMovement::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.transfers.index')->with('success', 'Transfer cancelled.');
    }
}
