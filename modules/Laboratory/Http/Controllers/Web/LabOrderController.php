<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabTest;
use App\Services\AuditLogger;
use App\Services\Laboratory\LabOrderLifecycleService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function __construct(
        private readonly LabOrderLifecycleService $lifecycle,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabOrder::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $orders = LabOrder::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%'.$request->input('search').'%'))
            ->latest('ordered_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lab.orders.index', compact('orders'));
    }

    public function show(LabOrder $order)
    {
        $this->authorize('view', $order);

        $order->load(['patient', 'encounter', 'items.test', 'items.results', 'specimens', 'reports']);

        $companyId = $order->company_id;
        $availableTests = LabTest::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.orders.show', compact('order', 'availableTests'));
    }

    public function resolveUnmatchedItem(Request $request, LabOrder $order)
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:lab_order_items,id'],
            'test_id' => ['required', 'integer', 'exists:lab_tests,id'],
        ]);

        $item = $order->items()->whereKey($validated['item_id'])->firstOrFail();
        $oldValues = $item->toArray();

        $item->update(['test_id' => $validated['test_id'], 'status' => 'pending']);

        $this->auditLogger->log('RESOLVE_UNMATCHED_ITEM', \App\Models\Laboratory\LabOrderItem::class, $item->id, $oldValues, $item->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $order)->with('success', 'Order item matched to a test.');
    }

    public function cancel(Request $request, LabOrder $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $order->toArray();

        $this->lifecycle->cancel($order, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', LabOrder::class, $order->id, $oldValues, $order->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $order)->with('success', 'Lab order cancelled.');
    }
}
