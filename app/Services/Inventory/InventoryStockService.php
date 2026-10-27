<?php

namespace App\Services\Inventory;

use App\Contracts\Stock\StockLedger;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\Stock\StockLedgerEntry;
use App\Models\User;
use App\Services\Stock\LedgerEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The only door through which enterprise stock moves (guideline 12.2). Every change is a ledger row first; `inventory_stock` is the projection
 * kept in step inside the same transaction and can be rebuilt from the ledger. Quantities are strings in BASE units (bcmath, 4 places).
 * Callers wrap their work in DB::transaction; the position row is locked so two people cannot take the same last unit.
 */
class InventoryStockService
{
    public const KIND = 'inventory';

    /** Movements that may take blocked (quarantined, recalled, expired) stock out of the store. */
    private const MAY_TAKE_BLOCKED = ['quarantine_out', 'recall', 'expiry_writeoff', 'wastage', 'adjustment_loss', 'return', 'transfer_out'];

    public function __construct(private readonly StockLedger $ledger) {}

    /** The batch record for a delivery; 0 when the item is not tracked by batch. */
    public function batch(InventoryItem $item, ?string $batchNo, ?string $expiry, ?int $supplierId, string $unitCost, ?string $receivedOn = null): int
    {
        if (! $item->tracks_batch) {
            return 0;
        }
        if ($batchNo === null || trim($batchNo) === '') {
            throw ValidationException::withMessages(['batch_no' => "{$item->name} needs a batch number."]);
        }
        if ($item->tracks_expiry && ! $expiry) {
            throw ValidationException::withMessages(['expiry_date' => "{$item->name} needs an expiry date."]);
        }
        $batch = InventoryBatch::query()->firstOrCreate(
            ['item_id' => $item->id, 'batch_no' => trim($batchNo), 'supplier_id' => $supplierId],
            ['organization_id' => $item->organization_id, 'expiry_date' => $expiry, 'received_on' => $receivedOn ?? now()->toDateString(), 'unit_cost' => $unitCost],
        );

        return $batch->id;
    }

    public function receive(InventoryStore $store, InventoryItem $item, int $batchId, string $quantity, string $txnType, string $key, ?string $unitCost = null, ?string $referenceType = null, ?int $referenceId = null, ?User $by = null, ?string $reason = null): StockLedgerEntry
    {
        $this->positive($quantity);
        $position = $this->position($store, $item, $batchId);
        $entry = $this->ledger->post(new LedgerEntry($store->organization_id, self::KIND, $store->id, $item->id, $quantity, $txnType, $batchId ?: null, $store->branch_id, null, $unitCost, $referenceType, $referenceId, $reason === null ? null : Str::limit($reason, 250, ''), $by?->id, null, $key));
        if ($entry->wasRecentlyCreated) {
            $position->update(['quantity_on_hand' => bcadd((string) $position->quantity_on_hand, $quantity, 4)]);
        }

        return $entry;
    }

    public function take(InventoryStore $store, InventoryItem $item, int $batchId, string $quantity, string $txnType, string $key, ?string $unitCost = null, ?string $referenceType = null, ?int $referenceId = null, ?User $by = null, ?string $reason = null): StockLedgerEntry
    {
        $this->positive($quantity);
        $position = $this->position($store, $item, $batchId);
        $existing = StockLedgerEntry::query()->where('organization_id', $store->organization_id)->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }
        $available = bcsub((string) $position->quantity_on_hand, (string) $position->quantity_reserved, 4);
        if (! $store->allow_negative && bccomp($available, $quantity, 4) < 0) {
            throw ValidationException::withMessages(['quantity' => "Not enough {$item->name} in {$store->name}: {$available} available, {$quantity} needed."]);
        }
        if ($batchId > 0 && ! in_array($txnType, self::MAY_TAKE_BLOCKED, true)) {
            $this->assertUsable(InventoryBatch::findOrFail($batchId), $item);
        }
        $entry = $this->ledger->post(new LedgerEntry($store->organization_id, self::KIND, $store->id, $item->id, bcmul($quantity, '-1', 4), $txnType, $batchId ?: null, $store->branch_id, null, $unitCost, $referenceType, $referenceId, $reason === null ? null : Str::limit($reason, 250, ''), $by?->id, null, $key));
        $position->update(['quantity_on_hand' => bcsub((string) $position->quantity_on_hand, $quantity, 4)]);

        return $entry;
    }

    /**
     * Which batches to take a quantity from: first-expiry-first-out (or first-in-first-out), only batches that are active and in date.
     *
     * @return list<array{batch_id: int, quantity: string, unit_cost: string}>
     */
    public function allocate(InventoryStore $store, InventoryItem $item, string $quantity): array
    {
        $this->positive($quantity);
        if (! $item->tracks_batch) {
            $position = InventoryStock::query()->where(['store_id' => $store->id, 'item_id' => $item->id, 'batch_id' => 0])->first();
            $available = $position ? bcsub((string) $position->quantity_on_hand, (string) $position->quantity_reserved, 4) : '0.0000';
            if (! $store->allow_negative && bccomp($available, $quantity, 4) < 0) {
                throw ValidationException::withMessages(['quantity' => "Not enough {$item->name} in {$store->name}: {$available} available, {$quantity} needed."]);
            }

            return [['batch_id' => 0, 'quantity' => $quantity, 'unit_cost' => (string) $item->standard_cost]];
        }

        $rows = InventoryStock::query()->where(['inventory_stock.store_id' => $store->id, 'inventory_stock.item_id' => $item->id])->where('inventory_stock.batch_id', '>', 0)
            ->join('inventory_batches as b', 'b.id', '=', 'inventory_stock.batch_id')
            ->where('b.status', 'active')->where(fn ($q) => $q->whereNull('b.expiry_date')->orWhere('b.expiry_date', '>=', now()->toDateString()))
            ->whereRaw('inventory_stock.quantity_on_hand - inventory_stock.quantity_reserved > 0')
            ->when($item->issue_rule === 'fifo', fn ($q) => $q->orderBy('b.received_on')->orderBy('b.id'), fn ($q) => $q->orderByRaw('b.expiry_date IS NULL')->orderBy('b.expiry_date')->orderBy('b.id'))
            ->get(['inventory_stock.batch_id', 'inventory_stock.quantity_on_hand', 'inventory_stock.quantity_reserved', 'b.unit_cost']);

        $left = $quantity;
        $picked = [];
        foreach ($rows as $row) {
            if (bccomp($left, '0', 4) <= 0) {
                break;
            }
            $free = bcsub((string) $row->quantity_on_hand, (string) $row->quantity_reserved, 4);
            $take = bccomp($free, $left, 4) < 0 ? $free : $left;
            $picked[] = ['batch_id' => (int) $row->batch_id, 'quantity' => $take, 'unit_cost' => (string) $row->getAttribute('unit_cost')];
            $left = bcsub($left, $take, 4);
        }
        if (bccomp($left, '0', 4) > 0) {
            $usable = bcsub($quantity, $left, 4);
            throw ValidationException::withMessages(['quantity' => "Not enough usable {$item->name} in {$store->name}: {$usable} in date, {$quantity} needed."]);
        }

        return $picked;
    }

    /** Stock that may be issued now (in date, not blocked), across batches. */
    public function usable(InventoryStore $store, InventoryItem $item): string
    {
        $total = '0.0000';
        $q = InventoryStock::query()->where(['inventory_stock.store_id' => $store->id, 'inventory_stock.item_id' => $item->id]);
        if ($item->tracks_batch) {
            $q->join('inventory_batches as b', 'b.id', '=', 'inventory_stock.batch_id')->where('b.status', 'active')->where(fn ($w) => $w->whereNull('b.expiry_date')->orWhere('b.expiry_date', '>=', now()->toDateString()));
        }
        foreach ($q->get(['inventory_stock.quantity_on_hand', 'inventory_stock.quantity_reserved']) as $row) {
            $total = bcadd($total, bcsub((string) $row->quantity_on_hand, (string) $row->quantity_reserved, 4), 4);
        }

        return $total;
    }

    /** Sets aside stock for a promised issue: it stays on hand but is no longer available (12.2 rule 7). */
    public function reserve(InventoryStore $store, InventoryItem $item, int $batchId, string $quantity): void
    {
        $this->positive($quantity);
        $position = $this->position($store, $item, $batchId);
        $available = bcsub((string) $position->quantity_on_hand, (string) $position->quantity_reserved, 4);
        if (bccomp($available, $quantity, 4) < 0) {
            throw ValidationException::withMessages(['quantity' => "Only {$available} of {$item->name} is free to reserve."]);
        }
        $position->update(['quantity_reserved' => bcadd((string) $position->quantity_reserved, $quantity, 4)]);
    }

    public function releaseReservation(InventoryStore $store, InventoryItem $item, int $batchId, string $quantity): void
    {
        $position = $this->position($store, $item, $batchId);
        $left = bcsub((string) $position->quantity_reserved, $quantity, 4);
        $position->update(['quantity_reserved' => bccomp($left, '0', 4) < 0 ? '0' : $left]);
    }

    public function onHand(InventoryStore $store, InventoryItem $item, ?int $batchId = null): string
    {
        return bcadd((string) InventoryStock::query()->where(['store_id' => $store->id, 'item_id' => $item->id])->when($batchId !== null, fn ($q) => $q->where('batch_id', $batchId))->sum('quantity_on_hand'), '0', 4);
    }

    /** Is this batch fit to give out? */
    public function assertUsable(InventoryBatch $batch, InventoryItem $item): void
    {
        if ($batch->status !== 'active') {
            throw ValidationException::withMessages(['batch' => "Batch {$batch->batch_no} of {$item->name} is {$batch->status} and cannot be issued."]);
        }
        if ($batch->expiry_date && $batch->expiry_date->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['batch' => "Batch {$batch->batch_no} of {$item->name} expired on {$batch->expiry_date->toDateString()}."]);
        }
    }

    private function position(InventoryStore $store, InventoryItem $item, int $batchId): InventoryStock
    {
        $keys = ['store_id' => $store->id, 'item_id' => $item->id, 'batch_id' => $batchId];
        $row = InventoryStock::query()->where($keys)->lockForUpdate()->first();
        if ($row) {
            return $row;
        }
        try {
            return InventoryStock::create($keys + ['organization_id' => $store->organization_id])->refresh();
        } catch (QueryException) {
            return InventoryStock::query()->where($keys)->lockForUpdate()->firstOrFail();
        }
    }

    private function positive(string $quantity): void
    {
        if (! is_numeric($quantity) || bccomp($quantity, '0', 4) <= 0) {
            throw ValidationException::withMessages(['quantity' => 'The quantity must be above zero.']);
        }
    }
}
