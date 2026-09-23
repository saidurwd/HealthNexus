<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyOrderItem;
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
            ->with(['patient', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%'.$request->input('search').'%'))
            ->latest('ordered_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.orders.index', compact('orders'));
    }

    public function show(PharmacyOrder $order)
    {
        $this->authorize('view', $order);

        $order->load(['patient', 'encounter', 'prescription', 'items.medication', 'items.safetyAlerts', 'dispensings.items']);

        $availableMedications = PharmacyMedication::query()->forTenant($order->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.pharmacy.orders.show', compact('order', 'availableMedications'));
    }

    public function resolveUnmatchedItem(Request $request, PharmacyOrder $order)
    {
        $this->authorize('review', $order);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:pharmacy_order_items,id'],
            'medication_id' => ['required', 'integer', 'exists:pharmacy_medications,id'],
        ]);

        $item = $order->items()->whereKey($validated['item_id'])->firstOrFail();
        $oldValues = $item->toArray();

        $item->update(['medication_id' => $validated['medication_id'], 'status' => PharmacyOrderItem::STATUS_PENDING]);

        $this->auditLogger->log('RESOLVE_UNMATCHED_ITEM', PharmacyOrderItem::class, $item->id, $oldValues, $item->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.orders.show', $order)->with('success', 'Order item matched to a medication.');
    }

    public function cancel(Request $request, PharmacyOrder $order)
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $order->toArray();

        $this->lifecycle->cancel($order, $validated['reason'], $request->user());

        $this->auditLogger->log('CANCEL', PharmacyOrder::class, $order->id, $oldValues, $order->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.orders.show', $order)->with('success', 'Pharmacy order cancelled.');
    }
}
