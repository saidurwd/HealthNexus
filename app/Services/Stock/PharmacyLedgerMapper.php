<?php

namespace App\Services\Stock;

/** Maps Pharmacy's own movement types onto the shared ledger txn_type vocabulary (guideline 12.2). */
final class PharmacyLedgerMapper
{
    public static function txnType(string $pharmacyType, string $direction): string
    {
        return match ($pharmacyType) {
            'opening' => 'opening_balance',
            'purchase_receipt' => 'purchase_receipt',
            'transfer_in' => 'transfer_in',
            'transfer_out' => 'transfer_out',
            'dispensing' => 'dispense',
            'return' => 'return',
            'damage', 'wastage' => 'wastage',
            'expired' => 'expiry_writeoff',
            default => $direction === 'in' ? 'adjustment_gain' : 'adjustment_loss', // adjustment, correction, anything new
        };
    }
}
