<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyControlledDrugTransaction;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Controlled-drug register: an append-only ledger mirroring AdvanceService, balance_after always
 * computed from the running total of prior transactions per (store, medication) — never a bare
 * mutable balance column. Must only be called from inside a transaction that already holds a
 * lockForUpdate() on the affected pharmacy_stock row (PharmacyStockService::credit()/debit()
 * always does), which is what serializes concurrent balance computation here.
 */
class PharmacyControlledDrugService
{
    public function record(PharmacyStore $store, PharmacyMedication $medication, ?PharmacyBatch $batch, int $quantity, string $type, string $direction, User $user, ?string $notes = null, ?User $witnessedBy = null, ?object $reference = null): PharmacyControlledDrugTransaction
    {
        return DB::transaction(function () use ($store, $medication, $batch, $quantity, $type, $direction, $user, $notes, $witnessedBy, $reference) {
            $currentBalance = $this->balance($store, $medication);
            $balanceAfter = $direction === 'in' ? $currentBalance + $quantity : $currentBalance - $quantity;

            return PharmacyControlledDrugTransaction::create([
                'company_id' => $medication->company_id,
                'branch_id' => $medication->branch_id,
                'store_id' => $store->id,
                'medication_id' => $medication->id,
                'batch_id' => $batch?->id,
                'type' => $type,
                'direction' => $direction,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'performed_by' => $user->id,
                'witnessed_by' => $witnessedBy?->id,
                'notes' => $notes,
            ]);
        });
    }

    public function balance(PharmacyStore $store, PharmacyMedication $medication): int
    {
        return PharmacyControlledDrugTransaction::query()
            ->where('store_id', $store->id)
            ->where('medication_id', $medication->id)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0;
    }
}
