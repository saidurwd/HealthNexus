<?php

namespace Modules\Radiology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Radiology\RadiologyOrder;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyOrderLifecycleService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyOrderController extends Controller
{
    public function __construct(
        private readonly RadiologyOrderLifecycleService $lifecycle,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyOrder::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $orders = RadiologyOrder::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'items.procedure'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('ordered_at')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($orders);
    }

    public function show(RadiologyOrder $order)
    {
        $this->authorize('view', $order);

        $order->load(['patient', 'encounter', 'items.procedure', 'items.examination.modality', 'items.examination.report']);

        return ApiResponse::success($order);
    }

    public function cancel(Request $request, RadiologyOrder $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $order->toArray();

        $this->lifecycle->cancel($order, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', RadiologyOrder::class, $order->id, $oldValues, $order->fresh()->toArray(), $request);

        return ApiResponse::success($order->fresh(), 'Radiology order cancelled.');
    }
}
