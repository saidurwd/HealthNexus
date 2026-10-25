<?php

namespace App\Contracts\Stock;

use App\Models\Stock\StockLedgerEntry;
use App\Services\Stock\LedgerEntry;

/** Append-only record of every stock movement in every store (pharmacy, later inventory). No stock change without a ledger row. */
interface StockLedger
{
    /** Posting the same idempotency key twice returns the first entry. */
    public function post(LedgerEntry $entry): StockLedgerEntry;

    /** On-hand according to the ledger. */
    public function onHand(int $organizationId, string $storeKind, int $storeId, int $itemId, ?int $batchId = null): float;
}
