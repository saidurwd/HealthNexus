<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementQuotation;
use App\Models\Inventory\ProcurementRequisition;
use App\Models\Inventory\ProcurementRfq;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Request for quotation, comparison and award (S05). The winning quotation becomes a draft purchase order at the quoted prices. */
class RfqService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly SupplierService $suppliers, private readonly PurchaseOrderService $orders) {}

    /** @param  list<ProcurementSupplier>  $invited */
    public function createFromRequisition(ProcurementRequisition $requisition, array $invited, User $by, ?string $closesOn = null): ProcurementRfq
    {
        if ($requisition->status !== 'approved') {
            throw ValidationException::withMessages(['requisition' => 'Only an approved requisition can go to quotation.']);
        }
        if (count($invited) < 1) {
            throw ValidationException::withMessages(['suppliers' => 'Invite at least one supplier.']);
        }
        foreach ($invited as $supplier) {
            $this->suppliers->assertCanOrder($supplier);
        }

        return DB::transaction(function () use ($requisition, $invited, $by, $closesOn) {
            $rfq = ProcurementRfq::create([
                'organization_id' => $requisition->organization_id, 'requisition_id' => $requisition->id, 'number' => $this->numbers->next('rfq', $requisition->organization_id, $by),
                'status' => 'sent', 'closes_on' => $closesOn, 'created_by' => $by->id,
            ]);
            foreach ($requisition->lines as $line) {
                $rfq->lines()->create(['item_id' => $line->item_id, 'quantity' => $line->quantity]);
            }
            foreach ($invited as $supplier) {
                $rfq->invitations()->create(['supplier_id' => $supplier->id, 'invited_at' => now()]);
            }

            return $rfq->refresh();
        });
    }

    /** @param  array<int, float|int|string>  $prices  rfq line id => unit price per purchase unit */
    public function recordQuotation(ProcurementRfq $rfq, ProcurementSupplier $supplier, array $prices, ?int $deliveryDays = null, ?string $validUntil = null, ?string $notes = null): ProcurementQuotation
    {
        if ($rfq->status !== 'sent') {
            throw ValidationException::withMessages(['rfq' => 'This request for quotation is not open.']);
        }
        if (! $rfq->invitations()->where('supplier_id', $supplier->id)->exists()) {
            throw ValidationException::withMessages(['supplier' => 'That supplier was not invited.']);
        }
        $lines = $rfq->lines;
        foreach ($lines as $line) {
            if (! isset($prices[$line->id]) || bccomp((string) $prices[$line->id], '0', 4) <= 0) {
                throw ValidationException::withMessages(['prices' => 'Give a price for every item.']);
            }
        }

        return DB::transaction(function () use ($rfq, $supplier, $prices, $deliveryDays, $validUntil, $notes, $lines) {
            ProcurementQuotation::query()->where(['rfq_id' => $rfq->id, 'supplier_id' => $supplier->id])->each(fn ($q) => $q->delete());
            $total = '0.00';
            $quotation = ProcurementQuotation::create(['organization_id' => $rfq->organization_id, 'rfq_id' => $rfq->id, 'supplier_id' => $supplier->id, 'delivery_days' => $deliveryDays, 'valid_until' => $validUntil, 'notes' => $notes]);
            foreach ($lines as $line) {
                $quotation->lines()->create(['rfq_line_id' => $line->id, 'unit_price' => $prices[$line->id]]);
                $total = bcadd($total, bcmul((string) $line->quantity, (string) $prices[$line->id], 2), 2);
            }
            $quotation->update(['total' => $total]);

            return $quotation->refresh();
        });
    }

    /** Side-by-side view: totals per supplier, the cheapest marked, and per-line prices. @return list<array<string, mixed>> */
    public function comparison(ProcurementRfq $rfq): array
    {
        $quotes = $rfq->quotations()->with(['supplier', 'lines'])->get();
        $lowest = $quotes->min(fn ($q) => (float) $q->total);

        return $quotes->map(fn ($q) => [
            'quotation_id' => $q->id, 'supplier_id' => $q->supplier_id, 'supplier' => $q->supplier->name, 'total' => (string) $q->total, 'delivery_days' => $q->delivery_days,
            'valid_until' => $q->valid_until?->toDateString(), 'status' => $q->status, 'is_lowest' => (float) $q->total === (float) $lowest, 'rating' => $q->supplier->rating,
            'prices' => $q->lines->pluck('unit_price', 'rfq_line_id')->map(fn ($p) => (string) $p)->all(),
        ])->sortBy('total')->values()->all();
    }

    /** Awards to one supplier and drafts the purchase order from its quotation. */
    public function award(ProcurementRfq $rfq, ProcurementSupplier $supplier, User $by, InventoryStore $deliverTo): ProcurementPurchaseOrder
    {
        if ($rfq->status !== 'sent') {
            throw ValidationException::withMessages(['rfq' => 'This request for quotation is already closed.']);
        }
        $quotation = $rfq->quotations()->where('supplier_id', $supplier->id)->with('lines')->first();
        if (! $quotation) {
            throw ValidationException::withMessages(['supplier' => 'That supplier has not quoted.']);
        }
        if ($quotation->valid_until && $quotation->valid_until->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['quotation' => 'That quotation has expired.']);
        }

        return DB::transaction(function () use ($rfq, $supplier, $by, $deliverTo, $quotation) {
            $prices = $quotation->lines->pluck('unit_price', 'rfq_line_id');
            $lines = $rfq->lines->map(fn ($l) => ['item_id' => $l->item_id, 'quantity' => $l->quantity, 'unit_price' => $prices[$l->id]])->all();
            $requisition = $rfq->requisition_id ? ProcurementRequisition::find($rfq->requisition_id) : null;
            $order = $this->orders->create($by, $supplier, $deliverTo, $lines, ['requisition_id' => $rfq->requisition_id, 'rfq_id' => $rfq->id, 'department_id' => $requisition?->department_id, 'expected_on' => $quotation->delivery_days ? now()->addDays($quotation->delivery_days)->toDateString() : null]);
            $rfq->quotations()->where('id', '!=', $quotation->id)->update(['status' => 'rejected']);
            $quotation->update(['status' => 'selected']);
            $rfq->update(['status' => 'awarded', 'awarded_supplier_id' => $supplier->id, 'purchase_order_id' => $order->id]);
            if ($requisition) {
                foreach ($requisition->lines as $line) {
                    $line->update(['ordered_quantity' => $line->quantity]);
                }
                $requisition->update(['status' => 'ordered']);
            }

            return $order;
        });
    }
}
