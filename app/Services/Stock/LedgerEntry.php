<?php

namespace App\Services\Stock;

use Carbon\CarbonInterface;

/**
 * One stock movement. `quantity` is signed (+ in, - out). Valid txn types (guideline 12.2): opening_balance purchase_receipt
 * transfer_out transfer_in issue dispense return wastage expiry_writeoff adjustment_gain adjustment_loss quarantine_in
 * quarantine_out recall.
 */
final class LedgerEntry
{
    public const TXN_TYPES = [
        'opening_balance', 'purchase_receipt', 'transfer_out', 'transfer_in', 'issue', 'dispense', 'return', 'wastage',
        'expiry_writeoff', 'adjustment_gain', 'adjustment_loss', 'quarantine_in', 'quarantine_out', 'recall',
    ];

    public function __construct(
        public readonly int $organizationId,
        public readonly string $storeKind,
        public readonly int $storeId,
        public readonly int $itemId,
        public readonly float|int|string $quantity,
        public readonly string $txnType,
        public readonly ?int $batchId = null,
        public readonly ?int $branchId = null,
        public readonly ?int $serialId = null,
        public readonly float|int|string|null $unitCost = null,
        public readonly ?string $referenceType = null,
        public readonly string|int|null $referenceId = null,
        public readonly ?string $reason = null,
        public readonly ?int $performedBy = null,
        public readonly ?CarbonInterface $performedAt = null,
        public readonly ?string $idempotencyKey = null,
    ) {}
}
