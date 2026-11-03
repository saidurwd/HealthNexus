<?php

namespace App\Services\Inventory;

use App\Contracts\Integration\FinanceGateway;
use App\Models\Inventory\ProcurementPoLine;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Three-way match (S10): what was ORDERED (purchase order), what ARRIVED and was accepted (goods receipts), what the supplier BILLS (invoice).
 * Inside the tolerance the invoice matches; outside it, it waits for a second person. A mismatch never reaches Finance until someone approves it.
 */
class SupplierInvoiceMatchingService
{
    public function __construct(private readonly InventoryPolicy $policy, private readonly FinanceGateway $finance) {}

    /** @param  list<array{po_line_id: int, quantity: float|int|string, unit_price: float|int|string}>  $lines */
    public function record(User $by, ProcurementSupplier $supplier, ProcurementPurchaseOrder $order, string $invoiceNumber, string $invoiceDate, array $lines, string $taxAmount = '0.00'): ProcurementSupplierInvoice
    {
        if ($order->supplier_id !== $supplier->id) {
            throw ValidationException::withMessages(['order' => 'That order is with a different supplier.']);
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one invoice line.']);
        }
        if (ProcurementSupplierInvoice::query()->where(['supplier_id' => $supplier->id, 'invoice_number' => $invoiceNumber])->exists()) {
            throw ValidationException::withMessages(['invoice_number' => 'That supplier invoice has already been recorded.']);
        }

        return DB::transaction(function () use ($by, $supplier, $order, $invoiceNumber, $invoiceDate, $lines, $taxAmount) {
            $subtotal = '0.00';
            foreach ($lines as $l) {
                $subtotal = bcadd($subtotal, bcmul((string) $l['quantity'], (string) $l['unit_price'], 2), 2);
            }
            $invoice = ProcurementSupplierInvoice::create([
                'organization_id' => $order->organization_id, 'supplier_id' => $supplier->id, 'purchase_order_id' => $order->id, 'invoice_number' => $invoiceNumber, 'invoice_date' => $invoiceDate,
                'due_on' => date('Y-m-d', strtotime($invoiceDate.' +'.$supplier->payment_terms_days.' days')), 'subtotal' => $subtotal, 'tax_amount' => $taxAmount, 'total' => bcadd($subtotal, $taxAmount, 2), 'recorded_by' => $by->id,
            ]);
            foreach ($lines as $l) {
                $poLine = ProcurementPoLine::query()->where('purchase_order_id', $order->id)->findOrFail($l['po_line_id']);
                $invoice->lines()->create(['po_line_id' => $poLine->id, 'quantity' => $l['quantity'], 'unit_price' => $l['unit_price']]);
            }

            return $this->match($invoice);
        });
    }

    public function match(ProcurementSupplierInvoice $invoice): ProcurementSupplierInvoice
    {
        $tolerance = (array) $this->policy->get('match_tolerance');
        $details = [];
        $variance = '0.00';
        $ok = true;
        foreach ($invoice->lines()->with('poLine.item')->get() as $line) {
            $po = $line->poLine;
            // what has been accepted and not yet billed on other invoices
            $billedElsewhere = (string) DB::table('procurement_supplier_invoice_lines as l')->join('procurement_supplier_invoices as i', 'i.id', '=', 'l.invoice_id')
                ->where('l.po_line_id', $po->id)->where('l.invoice_id', '!=', $invoice->id)->whereNotIn('i.status', ['rejected'])->sum('l.quantity');
            $billable = bcsub((string) $po->received_quantity, $billedElsewhere, 4);
            $qtyDiff = bcsub((string) $line->quantity, $billable, 4);
            $qtyAllowed = bcdiv(bcmul($billable, (string) $tolerance['quantity_percent'], 4), '100', 4);
            $priceDiff = bcsub((string) $line->unit_price, (string) $po->unit_price, 4);
            $priceAllowed = bcdiv(bcmul((string) $po->unit_price, (string) $tolerance['price_percent'], 4), '100', 4);
            $qtyBad = bccomp($qtyDiff, $qtyAllowed, 4) > 0;      // billed for more than arrived
            $priceBad = bccomp($priceDiff, $priceAllowed, 4) > 0; // charged more than ordered
            $lineVariance = bcadd(bcmul($qtyDiff, (string) $po->unit_price, 2), bcmul((string) $line->quantity, $priceDiff, 2), 2);
            $variance = bcadd($variance, $lineVariance, 2);
            $ok = $ok && ! $qtyBad && ! $priceBad;
            $details[] = ['item' => $po->item->name, 'billed_quantity' => (string) $line->quantity, 'accepted_unbilled' => $billable, 'ordered_price' => (string) $po->unit_price, 'billed_price' => (string) $line->unit_price, 'quantity_ok' => ! $qtyBad, 'price_ok' => ! $priceBad];
        }
        if ($ok === false && bccomp($variance, (string) $tolerance['amount'], 2) <= 0 && bccomp($variance, '0', 2) <= 0) {
            $ok = true; // only in the supplier's disfavour
        }
        $invoice->update(['status' => $ok ? 'matched' : 'mismatch', 'variance_amount' => $variance, 'match_result' => $details]);

        return $invoice->refresh();
    }

    public function approveMismatch(ProcurementSupplierInvoice $invoice, User $by, string $note): ProcurementSupplierInvoice
    {
        if ($invoice->status !== 'mismatch') {
            throw ValidationException::withMessages(['status' => 'Only an invoice that does not match needs approval.']);
        }
        if ($invoice->recorded_by === $by->id) {
            throw ValidationException::withMessages(['approval' => 'A second person must approve a mismatched invoice.']);
        }
        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'Say why the difference is accepted.']);
        }
        $invoice->update(['status' => 'approved', 'approved_by' => $by->id, 'approval_note' => $note]);

        return $invoice->refresh();
    }

    public function reject(ProcurementSupplierInvoice $invoice, User $by, string $note): ProcurementSupplierInvoice
    {
        if (! in_array($invoice->status, ['mismatch', 'recorded'], true)) {
            throw ValidationException::withMessages(['status' => 'This invoice cannot be rejected now.']);
        }
        $invoice->update(['status' => 'rejected', 'approved_by' => $by->id, 'approval_note' => $note]);

        return $invoice->refresh();
    }

    /** Only a matched or approved invoice becomes a payable. @return bool true when Finance took it */
    public function handToFinance(ProcurementSupplierInvoice $invoice): bool
    {
        if (! in_array($invoice->status, ['matched', 'approved'], true)) {
            throw ValidationException::withMessages(['status' => 'An invoice that does not match cannot be paid.']);
        }
        if ($invoice->finance_reference) {
            return true;
        }
        $result = $this->finance->postPayable([
            'organization_id' => $invoice->organization_id, 'supplier_id' => $invoice->supplier_id, 'invoice_number' => $invoice->invoice_number, 'invoice_date' => $invoice->invoice_date->toDateString(),
            'due_on' => $invoice->due_on?->toDateString(), 'amount' => (string) $invoice->total, 'purchase_order_id' => $invoice->purchase_order_id, 'source' => 'inventory:'.$invoice->public_id,
        ]);
        if ($result->handled) {
            $invoice->update(['finance_reference' => (string) ($result->data['reference'] ?? 'POSTED')]);
        }

        return $result->handled;
    }
}
