<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementGoodsReceipt;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Models\Inventory\ProcurementSupplierReturn;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Inventory\SupplierInvoiceMatchingService;
use App\Services\Inventory\SupplierReturnService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Goods received and inspected, supplier invoices matched, goods returned. */
class ProcurementReceivingController extends InventoryController
{
    public function __construct(TenantContextResolver $tenant, private readonly GoodsReceiptService $receipts, private readonly SupplierInvoiceMatchingService $matching, private readonly SupplierReturnService $returns)
    {
        parent::__construct($tenant);
    }

    public function receiptsIndex(Request $request)
    {
        $this->allow($request, 'procurement.grn.view');
        $org = $this->organizationId();

        return view('admin.procurement.receipts', [
            'rows' => ProcurementGoodsReceipt::query()->with(['order:id,number', 'supplier:id,name', 'lines.item:id,code,name'])->where('organization_id', $org)->orderByRaw("status = 'pending_inspection' desc")->latest('id')->paginate(25),
            'due' => ProcurementPurchaseOrder::query()->with(['supplier:id,name', 'lines.item:id,code,name,tracks_batch,tracks_expiry'])->where('organization_id', $org)->whereIn('status', ['issued', 'partially_received'])->orderBy('expected_on')->limit(30)->get(),
        ]);
    }

    public function receive(Request $request, ProcurementPurchaseOrder $order): RedirectResponse
    {
        $this->scoped($request, $order, 'procurement.grn.receive');
        $v = $request->validate(['lines' => ['required', 'array'], 'lines.*.quantity' => ['nullable', 'numeric', 'min:0'], 'lines.*.batch_no' => ['nullable', 'string', 'max:60'], 'lines.*.expiry_date' => ['nullable', 'date'], 'delivery_note' => ['nullable', 'string', 'max:80']]);
        $rows = [];
        foreach ($v['lines'] as $lineId => $row) {
            if (($row['quantity'] ?? 0) > 0) {
                $rows[] = ['po_line_id' => (int) $lineId, 'quantity' => $row['quantity'], 'batch_no' => $row['batch_no'] ?? null, 'expiry_date' => $row['expiry_date'] ?? null];
            }
        }
        $receipt = $this->receipts->receive($order, $request->user(), $rows, $v['delivery_note'] ?? null);

        return redirect()->route('admin.procurement.receipts.show', $receipt)->with('success', __('Delivery counted in. Now inspect it.'));
    }

    public function showReceipt(Request $request, ProcurementGoodsReceipt $receipt)
    {
        $this->scoped($request, $receipt, 'procurement.grn.view');

        return view('admin.procurement.receipt', ['receipt' => $receipt->load(['order', 'supplier', 'lines.item'])]);
    }

    public function inspect(Request $request, ProcurementGoodsReceipt $receipt): RedirectResponse
    {
        $this->scoped($request, $receipt, 'procurement.grn.inspect');
        $v = $request->validate(['rejected' => ['nullable', 'array'], 'rejected.*' => ['nullable', 'numeric', 'min:0'], 'reason' => ['nullable', 'array'], 'reason.*' => ['nullable', 'string', 'max:200']]);
        $decisions = [];
        foreach ($receipt->lines as $line) {
            $rejected = (string) ($v['rejected'][$line->id] ?? '0');
            $decisions[$line->id] = ['rejected' => $rejected, 'accepted' => bcsub((string) $line->received_quantity, $rejected, 4), 'reason' => $v['reason'][$line->id] ?? null];
        }
        $this->receipts->inspect($receipt, $request->user(), $decisions);

        return back()->with('success', __('Inspected and stocked.'));
    }

    public function invoicesIndex(Request $request)
    {
        $this->allow($request, 'procurement.invoice.view');
        $org = $this->organizationId();

        return view('admin.procurement.invoices', [
            'rows' => ProcurementSupplierInvoice::query()->with(['supplier:id,name', 'order:id,number'])->where('organization_id', $org)->orderByRaw("status = 'mismatch' desc")->latest('id')->paginate(25),
            'orders' => ProcurementPurchaseOrder::query()->with(['supplier:id,name', 'lines.item:id,name'])->where('organization_id', $org)->whereIn('status', ['partially_received', 'received', 'closed'])->latest('id')->limit(30)->get(),
        ]);
    }

    public function recordInvoice(Request $request, ProcurementPurchaseOrder $order): RedirectResponse
    {
        $this->scoped($request, $order, 'procurement.invoice.record');
        $v = $request->validate(['invoice_number' => ['required', 'string', 'max:60'], 'invoice_date' => ['required', 'date'], 'lines' => ['required', 'array'], 'lines.*.quantity' => ['nullable', 'numeric', 'min:0'], 'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'], 'tax_amount' => ['nullable', 'numeric', 'min:0']]);
        $lines = [];
        foreach ($v['lines'] as $lineId => $row) {
            if (($row['quantity'] ?? 0) > 0) {
                $lines[] = ['po_line_id' => (int) $lineId, 'quantity' => $row['quantity'], 'unit_price' => $row['unit_price'] ?? 0];
            }
        }
        $invoice = $this->matching->record($request->user(), $order->supplier, $order, $v['invoice_number'], $v['invoice_date'], $lines, (string) ($v['tax_amount'] ?? '0.00'));

        return back()->with($invoice->status === 'matched' ? 'success' : 'warning', $invoice->status === 'matched' ? __('Invoice matches the order and the goods received.') : __('Invoice does not match; a second person must review it.'));
    }

    public function decideInvoice(Request $request, ProcurementSupplierInvoice $invoice): RedirectResponse
    {
        $action = $request->input('action');
        if ($action === 'finance') {
            $this->scoped($request, $invoice, 'procurement.invoice.view');
            $this->matching->handToFinance($invoice);

            return back()->with('success', __('Passed on for payment.'));
        }
        $this->scoped($request, $invoice, 'procurement.invoice.approve');
        $v = $request->validate(['note' => ['required', 'string', 'max:300']]);
        $action === 'reject' ? $this->matching->reject($invoice, $request->user(), $v['note']) : $this->matching->approveMismatch($invoice, $request->user(), $v['note']);

        return back()->with('warning', __('Invoice decision saved.'));
    }

    public function returnsIndex(Request $request)
    {
        $this->allow($request, 'procurement.return.manage');
        $org = $this->organizationId();
        $qrn = InventoryStore::where(['organization_id' => $org, 'kind' => 'quarantine'])->first();

        return view('admin.procurement.returns', [
            'rows' => ProcurementSupplierReturn::query()->with(['supplier:id,name', 'lines.item:id,code,name'])->where('organization_id', $org)->latest('id')->paginate(20),
            'held' => $qrn ? InventoryStock::query()->with(['item:id,code,name', 'store:id,public_id,name'])->where('store_id', $qrn->id)->where('quantity_on_hand', '>', 0)->get() : collect(),
            'batches' => InventoryBatch::whereIn('id', $qrn ? InventoryStock::where('store_id', $qrn->id)->pluck('batch_id') : [])->pluck('batch_no', 'id'),
            'suppliers' => ProcurementSupplier::where('organization_id', $org)->orderBy('name')->get(),
        ]);
    }

    public function storeReturn(Request $request): RedirectResponse
    {
        $this->allow($request, 'procurement.return.manage');
        $v = $request->validate(['supplier' => ['required', 'string'], 'position' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:300']]);
        $position = InventoryStock::query()->where('organization_id', $this->organizationId())->findOrFail($v['position']);
        $this->returns->create($request->user(), $this->findSupplier($v['supplier']), InventoryStore::findOrFail($position->store_id), [['item_id' => $position->item_id, 'batch_id' => $position->batch_id, 'quantity' => $v['quantity']]], $v['reason']);

        return back()->with('success', __('Return prepared.'));
    }

    public function moveReturn(Request $request, ProcurementSupplierReturn $return): RedirectResponse
    {
        $this->scoped($request, $return, 'procurement.return.manage');
        if ($request->input('action') === 'credit') {
            $v = $request->validate(['credit_note' => ['required', 'string', 'max:60'], 'amount' => ['required', 'numeric', 'min:0']]);
            $this->returns->credit($return, $v['credit_note'], (string) $v['amount']);

            return back()->with('success', __('Credit note recorded.'));
        }
        $this->returns->dispatch($return, $request->user());

        return back()->with('success', __('Goods sent back to the supplier.'));
    }
}
