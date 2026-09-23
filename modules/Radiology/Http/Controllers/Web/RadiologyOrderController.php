<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use App\Models\Radiology\RadiologyProcedure;
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
            ->with(['patient', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('order_number', 'like', '%'.$request->input('search').'%')
                ->orWhere('accession_number', 'like', '%'.$request->input('search').'%')))
            ->latest('ordered_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.radiology.orders.index', compact('orders'));
    }

    public function show(RadiologyOrder $order)
    {
        $this->authorize('view', $order);

        $order->load(['patient', 'encounter', 'items.procedure', 'items.examination.modality', 'items.examination.studies', 'items.examination.report']);

        $companyId = $order->company_id;
        $availableProcedures = RadiologyProcedure::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.radiology.orders.show', compact('order', 'availableProcedures'));
    }

    public function resolveUnmatchedItem(Request $request, RadiologyOrder $order)
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:radiology_order_items,id'],
            'procedure_id' => ['required', 'integer', 'exists:radiology_procedures,id'],
        ]);

        $item = $order->items()->whereKey($validated['item_id'])->firstOrFail();
        $oldValues = $item->toArray();

        $item->update(['procedure_id' => $validated['procedure_id'], 'status' => 'pending']);

        $this->auditLogger->log('RESOLVE_UNMATCHED_ITEM', RadiologyOrderItem::class, $item->id, $oldValues, $item->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.orders.show', $order)->with('success', 'Order item matched to a procedure.');
    }

    public function cancel(Request $request, RadiologyOrder $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $order->toArray();

        $this->lifecycle->cancel($order, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', RadiologyOrder::class, $order->id, $oldValues, $order->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.orders.show', $order)->with('success', 'Radiology order cancelled.');
    }
}
