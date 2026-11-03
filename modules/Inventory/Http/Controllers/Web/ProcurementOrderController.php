<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementRequisition;
use App\Models\Inventory\ProcurementRfq;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Services\Inventory\ApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\ReorderService;
use App\Services\Inventory\RequisitionService;
use App\Services\Inventory\RfqService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Requisitions, requests for quotation, purchase orders (including emergency orders) and reorder suggestions. */
class ProcurementOrderController extends InventoryController
{
    public function __construct(
        TenantContextResolver $tenant,
        private readonly RequisitionService $requisitions,
        private readonly RfqService $rfqs,
        private readonly PurchaseOrderService $orders,
        private readonly ApprovalService $approvals,
        private readonly ReorderService $reorder,
    ) {
        parent::__construct($tenant);
    }

    public function requisitionsIndex(Request $request)
    {
        $this->allow($request, 'procurement.requisition.view');
        $rows = ProcurementRequisition::query()->with(['store:id,name', 'department:id,name', 'lines.item:id,code,name,purchase_uom,base_uom'])->where('organization_id', $this->organizationId())
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->orderByRaw("status = 'submitted' desc")->latest('id')->paginate(25)->withQueryString();

        return view('admin.procurement.requisitions', ['rows' => $rows, 'stores' => $this->storeChoices(), 'items' => $this->itemChoices(), 'departments' => $this->departmentChoices(), 'chains' => $rows->getCollection()->mapWithKeys(fn ($r) => [$r->id => $this->approvals->chain('requisition', $r->id)])]);
    }

    public function storeRequisition(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.requisition.create');
        $v = $request->validate(['store' => ['required', 'string'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0'], 'department_id' => ['nullable', 'integer'], 'priority' => ['nullable', 'in:routine,urgent'], 'needed_by' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:300']]);
        $store = $this->findStore($v['store']);
        $requisition = $this->requisitions->create($request->user(), $store, [['item_id' => $this->findItem($v['item'])->id, 'quantity' => $v['quantity']]], [
            'department_id' => isset($v['department_id']) ? $this->findDepartment((int) $v['department_id'])->id : null, 'priority' => $v['priority'] ?? 'routine', 'needed_by' => $v['needed_by'] ?? null, 'notes' => $v['notes'] ?? null,
        ]);
        $this->requisitions->submit($requisition, $request->user());

        return back()->with('success', __('Requisition :n sent for approval.', ['n' => $requisition->number]));
    }

    public function decideRequisition(Request $request, ProcurementRequisition $requisition): RedirectResponse
    {
        $action = $request->input('action');
        if ($action === 'cancel') {
            $this->scoped($request, $requisition, 'procurement.requisition.create');
            $this->requisitions->cancel($requisition);

            return back()->with('warning', __('Requisition cancelled.'));
        }
        $this->scoped($request, $requisition, 'procurement.requisition.view');
        if ($action === 'reject') {
            $v = $request->validate(['reason' => ['required', 'string', 'max:300']]);
            $this->requisitions->reject($requisition, $request->user(), $v['reason']);

            return back()->with('warning', __('Requisition rejected.'));
        }
        $this->requisitions->approve($requisition, $request->user(), $request->input('comment'));

        return back()->with('success', __('Approved.'));
    }

    public function reorderIndex(Request $request)
    {
        $this->allow($request, 'procurement.requisition.view');

        return view('admin.procurement.reorder', ['rows' => $this->reorder->suggestions($this->organizationId()), 'stores' => $this->storeChoices()]);
    }

    public function raiseFromReorder(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.requisition.create');
        $v = $request->validate(['store' => ['required', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*' => ['integer']]);
        $requisition = $this->reorder->raiseRequisition($request->user(), $this->findStore($v['store']), $v['items']);
        $this->requisitions->submit($requisition, $request->user());

        return redirect()->route('admin.procurement.requisitions')->with('success', __('Requisition :n raised from the suggestions.', ['n' => $requisition->number]));
    }

    public function rfqsIndex(Request $request)
    {
        $this->allow($request, 'procurement.rfq.view');
        $org = $this->organizationId();

        return view('admin.procurement.rfqs', [
            'rows' => ProcurementRfq::query()->where('organization_id', $org)->latest('id')->paginate(20),
            'approved' => ProcurementRequisition::query()->where(['organization_id' => $org, 'status' => 'approved'])->orderBy('id')->get(),
            'suppliers' => ProcurementSupplier::where(['organization_id' => $org, 'status' => 'qualified'])->orderBy('name')->get(),
        ]);
    }

    public function storeRfq(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.rfq.manage');
        $v = $request->validate(['requisition' => ['required', 'string'], 'suppliers' => ['required', 'array', 'min:1'], 'suppliers.*' => ['string'], 'closes_on' => ['nullable', 'date']]);
        $requisition = ProcurementRequisition::where('organization_id', $this->organizationId())->where('public_id', $v['requisition'])->firstOrFail();
        $rfq = $this->rfqs->createFromRequisition($requisition, array_map(fn ($id) => $this->findSupplier($id), $v['suppliers']), $request->user(), $v['closes_on'] ?? null);

        return redirect()->route('admin.procurement.rfqs.show', $rfq)->with('success', __('Request for quotation sent.'));
    }

    public function showRfq(Request $request, ProcurementRfq $rfq)
    {
        $this->scoped($request, $rfq, 'procurement.rfq.view');
        $rfq->load(['lines.item:id,code,name,purchase_uom,base_uom', 'invitations.supplier']);

        return view('admin.procurement.rfq', ['rfq' => $rfq, 'comparison' => $this->rfqs->comparison($rfq), 'stores' => $this->storeChoices()]);
    }

    public function quote(Request $request, ProcurementRfq $rfq): RedirectResponse
    {
        $this->scoped($request, $rfq, 'procurement.rfq.manage');
        $v = $request->validate(['supplier' => ['required', 'string'], 'prices' => ['required', 'array'], 'prices.*' => ['required', 'numeric', 'gt:0'], 'delivery_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'valid_until' => ['nullable', 'date']]);
        $this->rfqs->recordQuotation($rfq, $this->findSupplier($v['supplier']), $v['prices'], $v['delivery_days'] ?? null, $v['valid_until'] ?? null);

        return back()->with('success', __('Quotation saved.'));
    }

    public function award(Request $request, ProcurementRfq $rfq): RedirectResponse
    {
        $this->scoped($request, $rfq, 'procurement.rfq.manage');
        $this->allow($request, 'procurement.po.create');
        $v = $request->validate(['supplier' => ['required', 'string'], 'store' => ['required', 'string']]);
        $order = $this->rfqs->award($rfq, $this->findSupplier($v['supplier']), $request->user(), $this->findStore($v['store']));

        return redirect()->route('admin.procurement.orders.show', $order)->with('success', __('Awarded: purchase order :n drafted.', ['n' => $order->number]));
    }

    public function ordersIndex(Request $request)
    {
        $this->allow($request, 'procurement.po.view');
        $org = $this->organizationId();
        $rows = ProcurementPurchaseOrder::query()->with(['supplier:id,name', 'store:id,name'])->where('organization_id', $org)
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->orderByRaw("status = 'pending_approval' desc")->latest('id')->paginate(25)->withQueryString();

        return view('admin.procurement.orders', [
            'rows' => $rows, 'statuses' => array_keys(config('inventory.po_transitions')), 'overdue' => $this->orders->overdueRatifications($org)->count(),
            'suppliers' => ProcurementSupplier::where(['organization_id' => $org, 'status' => 'qualified'])->orderBy('name')->get(), 'stores' => $this->storeChoices(), 'items' => $this->itemChoices(), 'departments' => $this->departmentChoices(),
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.po.create');
        $v = $request->validate(['supplier' => ['required', 'string'], 'store' => ['required', 'string'], 'item' => ['required', 'string'], 'quantity' => ['required', 'numeric', 'gt:0'], 'unit_price' => ['nullable', 'numeric', 'gt:0'], 'department_id' => ['nullable', 'integer'], 'emergency_reason' => ['nullable', 'string', 'max:300'], 'expected_on' => ['nullable', 'date']]);
        $line = [['item_id' => $this->findItem($v['item'])->id, 'quantity' => $v['quantity'], 'unit_price' => $v['unit_price'] ?? null]];
        $options = ['department_id' => isset($v['department_id']) ? $this->findDepartment((int) $v['department_id'])->id : null, 'expected_on' => $v['expected_on'] ?? null];
        if (! empty($v['emergency_reason'])) {
            $this->allow($request, 'procurement.emergency.create');
            $order = $this->orders->createEmergency($request->user(), $this->findSupplier($v['supplier']), $this->findStore($v['store']), $line, $v['emergency_reason'], $options);

            return redirect()->route('admin.procurement.orders.show', $order)->with('warning', __('Emergency order issued. It must be approved within :h hours.', ['h' => config('inventory.emergency_ratify_hours')]));
        }
        $order = $this->orders->create($request->user(), $this->findSupplier($v['supplier']), $this->findStore($v['store']), $line, $options);

        return redirect()->route('admin.procurement.orders.show', $order)->with('success', __('Purchase order drafted.'));
    }

    public function showOrder(Request $request, ProcurementPurchaseOrder $order)
    {
        $this->scoped($request, $order, 'procurement.po.view');
        $order->load(['supplier', 'store', 'lines.item', 'amendments']);

        return view('admin.procurement.order', ['order' => $order, 'chain' => $this->approvals->chain('purchase_order', $order->id), 'receipts' => $order->receipts()->with('lines')->latest('id')->get(), 'invoices' => ProcurementSupplierInvoice::where('purchase_order_id', $order->id)->latest('id')->get()]);
    }

    public function moveOrder(Request $request, ProcurementPurchaseOrder $order): RedirectResponse
    {
        $action = (string) $request->input('action');
        $by = $request->user();
        switch ($action) {
            case 'submit':
                $this->scoped($request, $order, 'procurement.po.create');
                $this->orders->submit($order, $by);
                break;
            case 'approve':
                $this->scoped($request, $order, 'procurement.po.view');
                $this->orders->approve($order, $by, $request->input('comment'));
                break;
            case 'reject':
                $this->scoped($request, $order, 'procurement.po.view');
                $this->orders->reject($order, $by, (string) $request->validate(['reason' => ['required', 'string', 'max:300']])['reason']);
                break;
            case 'issue':
                $this->scoped($request, $order, 'procurement.po.issue');
                $this->orders->issue($order, $by);
                break;
            case 'cancel':
                $this->scoped($request, $order, 'procurement.po.create');
                $this->orders->cancel($order, (string) $request->validate(['reason' => ['required', 'string', 'max:300']])['reason']);
                break;
            case 'close':
                $this->scoped($request, $order, 'procurement.po.issue');
                $this->orders->close($order);
                break;
            case 'ratify':
                $this->scoped($request, $order, 'procurement.po.view');
                $this->orders->ratify($order, $by, $request->input('comment'));
                break;
            case 'refuse':
                $this->scoped($request, $order, 'procurement.po.view');
                $this->orders->refuseRatification($order, $by, (string) $request->validate(['reason' => ['required', 'string', 'max:300']])['reason']);
                break;
            case 'amend':
                $this->scoped($request, $order, 'procurement.po.amend');
                $v = $request->validate(['line' => ['required', 'integer'], 'quantity' => ['nullable', 'numeric', 'gt:0'], 'unit_price' => ['nullable', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:300']]);
                $this->orders->amend($order, $by, [array_filter(['line_id' => (int) $v['line'], 'quantity' => $v['quantity'] ?? null, 'unit_price' => $v['unit_price'] ?? null], fn ($x) => $x !== null)], $v['reason']);
                break;
            default:
                abort(422);
        }

        return back()->with('success', __('Done.'));
    }
}
