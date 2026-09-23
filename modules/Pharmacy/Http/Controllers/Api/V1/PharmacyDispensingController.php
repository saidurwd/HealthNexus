<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyReturnService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PharmacyDispensingController extends Controller
{
    public function __construct(
        private readonly PharmacyDispensingService $dispensing,
        private readonly PharmacyReturnService $returns,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyDispensing::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $dispensings = PharmacyDispensing::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'store'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('dispensed_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($dispensings);
    }

    public function show(PharmacyDispensing $dispensing)
    {
        $this->authorize('view', $dispensing);

        $dispensing->load(['patient', 'store', 'order', 'items.medication', 'items.batch']);

        return ApiResponse::success($dispensing);
    }

    public function store(Request $request, PharmacyOrder $order)
    {
        $this->authorize('create', PharmacyDispensing::class);

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['required', 'integer', 'exists:pharmacy_order_items,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.medication_id' => ['nullable', 'integer', 'exists:pharmacy_medications,id'],
            'lines.*.substitution_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $store = PharmacyStore::query()->findOrFail($validated['store_id']);

        try {
            $dispensing = $this->dispensing->dispense($order, $store, $validated['lines'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('CREATE', PharmacyDispensing::class, $dispensing->id, null, $dispensing->toArray(), $request);

        return ApiResponse::success($dispensing, 'Medications dispensed.', 201);
    }

    public function verify(Request $request, PharmacyDispensing $dispensing)
    {
        $this->authorize('verify', $dispensing);

        $oldValues = $dispensing->toArray();
        $this->dispensing->verify($dispensing, $request->user());

        $this->auditLogger->log('VERIFY', PharmacyDispensing::class, $dispensing->id, $oldValues, $dispensing->fresh()->toArray(), $request);

        return ApiResponse::success($dispensing->fresh(), 'Dispensing verified.');
    }

    public function storeReturn(Request $request, PharmacyDispensing $dispensing)
    {
        $this->authorize('return', $dispensing);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dispensing_item_id' => ['required', 'integer', 'exists:pharmacy_dispensing_items,id'],
            'items.*.quantity_returned' => ['required', 'integer', 'min:1'],
        ]);

        $return = $this->returns->request($dispensing, $validated['items'], $validated['reason'], $request->user());

        $this->auditLogger->log('CREATE', \App\Models\Pharmacy\PharmacyReturn::class, $return->id, null, $return->toArray(), $request);

        return ApiResponse::success($return, 'Return requested.', 201);
    }
}
