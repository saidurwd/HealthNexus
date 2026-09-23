<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
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
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($transfers);
    }

    public function show(PharmacyTransfer $transfer)
    {
        $this->authorize('view', $transfer);

        $transfer->load(['sourceStore', 'destinationStore', 'items.medication', 'items.batch']);

        return ApiResponse::success($transfer);
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
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CREATE', PharmacyTransfer::class, $transfer->id, null, $transfer->toArray(), $request);

        return ApiResponse::success($transfer, 'Transfer requested.', 201);
    }

    public function approve(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('approve', $transfer);

        $oldValues = $transfer->toArray();
        $this->transfers->approve($transfer, $request->user());

        $this->auditLogger->log('APPROVE', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return ApiResponse::success($transfer->fresh(), 'Transfer approved.');
    }

    public function dispatch(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('dispatch', $transfer);

        $oldValues = $transfer->toArray();

        try {
            $this->transfers->dispatch($transfer, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('DISPATCH', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return ApiResponse::success($transfer->fresh(), 'Transfer dispatched.');
    }

    public function receive(Request $request, PharmacyTransfer $transfer)
    {
        $this->authorize('receive', $transfer);

        $oldValues = $transfer->toArray();
        $this->transfers->receive($transfer, $request->user());

        $this->auditLogger->log('RECEIVE', PharmacyTransfer::class, $transfer->id, $oldValues, $transfer->fresh()->toArray(), $request);

        return ApiResponse::success($transfer->fresh(), 'Transfer received.');
    }
}
