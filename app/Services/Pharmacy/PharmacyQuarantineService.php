<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyQuarantine;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Quarantining a batch flips pharmacy_batches.is_quarantined — the single flag
 * PharmacyBatchSelectionService already checks to exclude a batch from FEFO everywhere, not just
 * at the store the quarantine was raised from. pharmacy_quarantine is the audit trail (who/why/
 * how much was on hand at the time), never a duplicate stock-holding mechanism. Quarantined stock
 * is only ever removed from pharmacy_stock at dispose() — quarantine itself never deletes stock.
 */
class PharmacyQuarantineService
{
    public function __construct(private readonly PharmacyStockService $stock) {}

    public function quarantine(PharmacyStore $store, PharmacyBatch $batch, int $quantity, string $reasonType, string $reason, User $user): PharmacyQuarantine
    {
        return DB::transaction(function () use ($store, $batch, $quantity, $reasonType, $reason, $user) {
            $batch->update(['is_quarantined' => true]);

            return PharmacyQuarantine::create([
                'company_id' => $store->company_id,
                'branch_id' => $store->branch_id,
                'store_id' => $store->id,
                'medication_id' => $batch->medication_id,
                'batch_id' => $batch->id,
                'quantity' => $quantity,
                'reason_type' => $reasonType,
                'reason' => $reason,
                'status' => PharmacyQuarantine::STATUS_QUARANTINED,
                'quarantined_by' => $user->id,
                'quarantined_at' => now(),
            ]);
        });
    }

    public function release(PharmacyQuarantine $quarantine, User $user): PharmacyQuarantine
    {
        if ($quarantine->status !== PharmacyQuarantine::STATUS_QUARANTINED) {
            throw ValidationException::withMessages(['quarantine' => "This record is already '{$quarantine->status}'."]);
        }

        return DB::transaction(function () use ($quarantine, $user) {
            $quarantine->loadMissing('batch');
            $quarantine->batch->update(['is_quarantined' => false]);

            $quarantine->update([
                'status' => PharmacyQuarantine::STATUS_RELEASED,
                'released_by' => $user->id,
                'released_at' => now(),
            ]);

            return $quarantine->refresh();
        });
    }

    public function dispose(PharmacyQuarantine $quarantine, User $user): PharmacyQuarantine
    {
        if ($quarantine->status !== PharmacyQuarantine::STATUS_QUARANTINED) {
            throw ValidationException::withMessages(['quarantine' => "This record is already '{$quarantine->status}'."]);
        }

        return DB::transaction(function () use ($quarantine, $user) {
            $quarantine->loadMissing('store', 'medication', 'batch');

            $this->stock->debit(
                $quarantine->store,
                $quarantine->medication,
                $quarantine->batch,
                $quarantine->quantity,
                PharmacyStockTransaction::TYPE_WASTAGE,
                $user,
                "Disposed under quarantine: {$quarantine->reason}",
                $quarantine,
            );

            $quarantine->update([
                'status' => PharmacyQuarantine::STATUS_DISPOSED,
                'disposed_by' => $user->id,
                'disposed_at' => now(),
            ]);

            return $quarantine->refresh();
        });
    }
}
