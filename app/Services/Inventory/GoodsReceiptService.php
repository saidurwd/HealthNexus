<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\ProcurementGoodsReceipt;
use App\Models\Inventory\ProcurementPoLine;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Receiving goods (S07). Delivery is first RECEIVED (counted at the door), then INSPECTED: accepted units go into the store, rejected units go
 * to the quarantine store and wait for return to the supplier. Partial deliveries are normal; a PO line closes when its accepted quantity is met.
 */
class GoodsReceiptService
{
    public function __construct(
        private readonly InventoryNumbers $numbers,
        private readonly InventoryStockService $stock,
        private readonly InventoryMasterService $masters,
        private readonly PurchaseOrderService $orders,
        private readonly InventoryPolicy $policy,
    ) {}

    /**
     * @param  list<array{po_line_id: int, quantity: float|int|string, batch_no?: ?string, expiry_date?: ?string}>  $lines  PURCHASE units
     */
    public function receive(ProcurementPurchaseOrder $order, User $by, array $lines, ?string $deliveryNote = null, ?string $receivedOn = null): ProcurementGoodsReceipt
    {
        if (! in_array($order->status, ['issued', 'partially_received'], true)) {
            throw ValidationException::withMessages(['order' => "Goods cannot be received on a {$order->status} order."]);
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Receive at least one line.']);
        }

        return DB::transaction(function () use ($order, $by, $lines, $deliveryNote, $receivedOn) {
            $order = ProcurementPurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            $receipt = ProcurementGoodsReceipt::create([
                'organization_id' => $order->organization_id, 'purchase_order_id' => $order->id, 'supplier_id' => $order->supplier_id, 'store_id' => $order->store_id,
                'number' => $this->numbers->next('grn', $order->organization_id, $by), 'delivery_note' => $deliveryNote, 'received_on' => $receivedOn ?? now()->toDateString(), 'received_by' => $by->id,
            ]);
            foreach ($lines as $row) {
                $line = ProcurementPoLine::query()->where('purchase_order_id', $order->id)->lockForUpdate()->findOrFail($row['po_line_id']);
                $item = InventoryItem::findOrFail($line->item_id);
                $quantity = (string) $row['quantity'];
                if (bccomp($quantity, '0', 4) <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Received quantities must be above zero.']);
                }
                $this->assertWithinOrder($line, $quantity, $item, $order);
                if ($item->tracks_batch && empty($row['batch_no'])) {
                    throw ValidationException::withMessages(['batch_no' => "{$item->name} needs a batch number."]);
                }
                if ($item->tracks_expiry && empty($row['expiry_date'])) {
                    throw ValidationException::withMessages(['expiry_date' => "{$item->name} needs an expiry date."]);
                }
                $receipt->lines()->create([
                    'po_line_id' => $line->id, 'item_id' => $item->id, 'received_quantity' => $quantity, 'batch_no' => $row['batch_no'] ?? null, 'expiry_date' => $row['expiry_date'] ?? null,
                    'unit_cost' => bcdiv((string) $line->unit_price, (string) $item->purchase_to_base, 4),
                ]);
            }

            return $receipt->refresh();
        });
    }

    /**
     * Records the inspection and stocks the goods.
     *
     * @param  array<int, array{accepted?: float|int|string, rejected?: float|int|string, reason?: ?string}>  $decisions  grn line id => decision. Lines not listed are accepted in full.
     */
    public function inspect(ProcurementGoodsReceipt $receipt, User $by, array $decisions = []): ProcurementGoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $by, $decisions) {
            $receipt = ProcurementGoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->status !== 'pending_inspection') {
                throw ValidationException::withMessages(['status' => 'This receipt has already been inspected.']);
            }
            $order = ProcurementPurchaseOrder::query()->lockForUpdate()->findOrFail($receipt->purchase_order_id);
            $store = $order->store;
            $quarantine = $this->masters->quarantineStore($receipt->organization_id);
            $minShelf = (int) $this->policy->get('min_remaining_shelf_life_days');

            foreach ($receipt->lines()->get() as $line) {
                $item = InventoryItem::findOrFail($line->item_id);
                $decision = $decisions[$line->id] ?? [];
                $accepted = (string) ($decision['accepted'] ?? $line->received_quantity);
                $rejected = (string) ($decision['rejected'] ?? bcsub((string) $line->received_quantity, $accepted, 4));
                $reason = $decision['reason'] ?? null;
                if (bccomp(bcadd($accepted, $rejected, 4), (string) $line->received_quantity, 4) !== 0 || bccomp($accepted, '0', 4) < 0 || bccomp($rejected, '0', 4) < 0) {
                    throw ValidationException::withMessages(['quantity' => "{$item->name}: accepted plus rejected must equal what was received."]);
                }
                // Too little shelf life left is a rejection, not a judgement call.
                if ($item->tracks_expiry && $line->expiry_date && bccomp($accepted, '0', 4) > 0 && $line->expiry_date->lt(now()->addDays($minShelf)->startOfDay())) {
                    $rejected = bcadd($rejected, $accepted, 4);
                    $accepted = '0.0000';
                    $reason ??= 'Less than '.$minShelf.' days of shelf life left';
                }
                if (bccomp($rejected, '0', 4) > 0 && ! $reason) {
                    throw ValidationException::withMessages(['reason' => "{$item->name}: say why the rejected units were rejected."]);
                }

                $poLine = ProcurementPoLine::query()->lockForUpdate()->findOrFail($line->po_line_id);
                $factor = (string) $item->purchase_to_base;
                $batchId = $this->stock->batch($item, $line->batch_no, $line->expiry_date?->toDateString(), $receipt->supplier_id, (string) $line->unit_cost, $receipt->received_on->toDateString());
                if (bccomp($accepted, '0', 4) > 0) {
                    $this->stock->receive($store, $item, $batchId, bcmul($accepted, $factor, 4), 'purchase_receipt', "GRN:{$receipt->id}:{$line->id}:ok", (string) $line->unit_cost, ProcurementGoodsReceipt::class, $receipt->id, $by);
                    $item->update(['last_cost' => $line->unit_cost]);
                }
                if (bccomp($rejected, '0', 4) > 0) {
                    // Rejected goods are on the premises but not usable: they sit in the quarantine store, on a batch record that cannot be issued.
                    $rejBatch = $item->tracks_batch ? $this->quarantineBatch($item, $line, $receipt->supplier_id, $reason) : 0;
                    $this->stock->receive($quarantine, $item, $rejBatch, bcmul($rejected, $factor, 4), 'purchase_receipt', "GRN:{$receipt->id}:{$line->id}:rej", (string) $line->unit_cost, ProcurementGoodsReceipt::class, $receipt->id, $by, $reason);
                }
                $line->update(['accepted_quantity' => $accepted, 'rejected_quantity' => $rejected, 'reject_reason' => $reason, 'batch_id' => $batchId ?: null]);
                $poLine->update(['received_quantity' => bcadd((string) $poLine->received_quantity, $accepted, 4), 'rejected_quantity' => bcadd((string) $poLine->rejected_quantity, $rejected, 4)]);
            }
            $receipt->update(['status' => 'posted', 'inspected_by' => $by->id, 'posted_at' => now()]);
            $this->orders->refreshReceiptStatus($order);

            return $receipt->refresh();
        });
    }

    private function quarantineBatch(InventoryItem $item, $line, int $supplierId, ?string $reason): int
    {
        // Same batch number as delivered, but a separate record per quarantine so the good batch is never blocked by a bad delivery.
        $batch = InventoryBatch::query()->create([
            'organization_id' => $item->organization_id, 'item_id' => $item->id, 'batch_no' => $line->batch_no.'/R'.$line->id, 'expiry_date' => $line->expiry_date, 'received_on' => now()->toDateString(),
            'supplier_id' => $supplierId, 'unit_cost' => $line->unit_cost, 'status' => 'quarantined', 'status_reason' => $reason,
        ]);

        return $batch->id;
    }

    private function assertWithinOrder(ProcurementPoLine $line, string $quantity, InventoryItem $item, ProcurementPurchaseOrder $order): void
    {
        $pending = ProcurementGoodsReceipt::query()->where('purchase_order_id', $order->id)->where('status', 'pending_inspection')
            ->join('procurement_grn_lines as g', 'g.goods_receipt_id', '=', 'procurement_goods_receipts.id')->where('g.po_line_id', $line->id)->sum('g.received_quantity');
        $open = bcsub((string) $line->quantity, bcadd((string) $line->received_quantity, (string) $pending, 4), 4);
        $allowed = bcadd($open, bcdiv(bcmul((string) $line->quantity, (string) $this->policy->get('over_receipt_percent'), 4), '100', 4), 4);
        if ($line->closed || bccomp($quantity, $allowed, 4) > 0) {
            throw ValidationException::withMessages(['quantity' => "{$item->name}: {$quantity} is more than the {$allowed} still due on the order."]);
        }
    }
}
