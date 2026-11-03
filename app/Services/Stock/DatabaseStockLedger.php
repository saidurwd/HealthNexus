<?php

namespace App\Services\Stock;

use App\Contracts\Stock\StockLedger;
use App\Models\Stock\StockLedgerEntry;
use Illuminate\Validation\ValidationException;

class DatabaseStockLedger implements StockLedger
{
    public function post(LedgerEntry $entry): StockLedgerEntry
    {
        if (! in_array($entry->txnType, LedgerEntry::TXN_TYPES, true)) {
            throw ValidationException::withMessages(['txn_type' => "Unknown stock transaction type [{$entry->txnType}]."]);
        }

        if (! is_numeric($entry->quantity) || (float) $entry->quantity == 0.0) {
            throw ValidationException::withMessages(['quantity' => 'A ledger entry needs a non-zero quantity.']);
        }

        if ($entry->idempotencyKey !== null) {
            $existing = StockLedgerEntry::query()
                ->where('organization_id', $entry->organizationId)->where('idempotency_key', $entry->idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }
        }

        return StockLedgerEntry::create([
            'organization_id' => $entry->organizationId, 'branch_id' => $entry->branchId, 'store_kind' => $entry->storeKind,
            'store_id' => $entry->storeId, 'item_id' => $entry->itemId, 'batch_id' => $entry->batchId, 'serial_id' => $entry->serialId,
            'quantity' => $entry->quantity, 'unit_cost' => $entry->unitCost, 'txn_type' => $entry->txnType,
            'reference_type' => $entry->referenceType, 'reference_id' => $entry->referenceId, 'reason' => $entry->reason,
            'performed_by' => $entry->performedBy, 'performed_at' => $entry->performedAt ?? now(), 'idempotency_key' => $entry->idempotencyKey,
        ]);
    }

    public function onHand(int $organizationId, string $storeKind, int $storeId, int $itemId, ?int $batchId = null): float
    {
        return (float) StockLedgerEntry::query()
            ->where(['organization_id' => $organizationId, 'store_kind' => $storeKind, 'store_id' => $storeId, 'item_id' => $itemId])
            ->when($batchId !== null, fn ($q) => $q->where('batch_id', $batchId))
            ->sum('quantity');
    }
}
