<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\Pharmacy\PharmacyTransfer;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyTransferService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PharmacyTransferController extends Controller
{
    public function __construct(
        private readonly PharmacyTransferService $transfers,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyTransfer::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $transfers = PharmacyTransfer::query()
            ->forTenant($companyId, $branchId)
            ->with(['sourceStore', 'destinationStore'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $this->authorize('create', PharmacyTransfer::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $stores = PharmacyStore::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $batches = PharmacyBatch::query()->where('company_id', $companyId)->where('is_quarantined', false)->with('medication')->orderBy('expiry_date')->get();

        return view('admin.pharmacy.transfers.create', compact('stores', 'batches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', PharmacyTransfer::class);

        $validated = $request->validate([
            'source_store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'destination_store_id' => ['required', 'integer', 'exists:pharmacy_stores,id', 'different:source_store_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'integer', 'exists:pharmacy_medications,id'],
            'items.*.batch_id' => ['required', 'integer', 'exists:pharmacy_batches,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
        ]);

        $source = PharmacyStore::query()->findOrFail($validated['source_store_id']);
        $destination = PharmacyStore::query()->findOrFail($validated['destination_store_id']);

        try {
            $transfer = $this->transfers->request($source, $destination, $validated['items'], $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->auditLogger->log('CREATE', PharmacyTransfer::class, $transfer->id, null, $transfer->toArray(), $request);

        return redirect()->route('admin.pharmacy.transfers.show', $transfer)->with('success', 'Transfer requested.');
    }

    public function show(PharmacyTransfer $transfer)
    {
        $this->authorize('view', $transfer);

        $transfer->load(['sourceStore', 'destinationStore', 'items.medication', 'items.batch']);

        return view('admin.pharmacy.transfers.show', compact('transfer'));
    }

    public function approve(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('approve', $transfer);

        $oldValues = $transfer->toArray();
        $this->transfers->approve($transfer, $request->user());

        $this->auditLogger->log('APPROVE', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.transfers.show', $transfer)->with('success', 'Transfer approved.');
    }

    public function dispatch(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('dispatch', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->dispatch($transfer, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('DISPATCH', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.transfers.show', $transfer)->with('success', 'Transfer dispatched.');
    }

    public function receive(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('receive', $transfer);

        $oldValues = $transfer->toArray();
        $this->transfers->receive($transfer, $request->user());

        $this->auditLogger->log('RECEIVE', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.transfers.show', $transfer)->with('success', 'Transfer received.');
    }
}
