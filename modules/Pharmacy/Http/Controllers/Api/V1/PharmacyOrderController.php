<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Pharmacy\PharmacyOrder;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyOrderLifecycleService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class PharmacyOrderController extends Controller
{
    public function __construct(
        private readonly PharmacyOrderLifecycleService $lifecycle,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyOrder::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $orders = PharmacyOrder::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'items.medication'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('ordered_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($orders);
    }

    public function show(PharmacyOrder $order)
    {
        $this->authorize('view', $order);

        $order->load(['patient', 'encounter', 'prescription', 'items.medication', 'items.safetyAlerts', 'dispensings.items']);

        return ApiResponse::success($order);
    }

    public function cancel(Request $request, PharmacyOrder $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $order->toArray();

        $this->lifecycle->cancel($order, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', PharmacyOrder::class, $order->id, $oldValues, $order->fresh()->toArray(), $request);

        return ApiResponse::success($order->fresh(), 'Pharmacy order cancelled.');
    }
}
