<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every pharmacy_stock mutation goes through here, inside DB::transaction() + lockForUpdate() on
 * the target stock row, paired with an append-only pharmacy_stock_transactions row — mirrors the
 * AdvanceService/BillingNumberGenerator locked-row discipline. Negative stock is always refused;
 * pharmacy.allow_negative_stock is a reserved, unwired settings flag (no override path exists).
 */
class PharmacyStockService
{
    public function receiveNewBatch(PharmacyStore $store, PharmacyMedication $medication, array $batchData, int $quantity, User $user, string $type = PharmacyStockTransaction::TYPE_PURCHASE_RECEIPT, ?object $reference = null): PharmacyBatch
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Received quantity must be at least 1.']);
        }

        return DB::transaction(function () use ($store, $medication, $batchData, $quantity, $user, $type, $reference) {
            $batch = PharmacyBatch::create([
                'company_id' => $medication->company_id,
                'branch_id' => $medication->branch_id,
                'medication_id' => $medication->id,
                'created_by' => $user->id,
                ...$batchData,
            ]);

            $this->credit($store, $medication, $batch, $quantity, $type, $user, null, $reference);

            return $batch->refresh();
        });
    }

    public function receiveIntoBatch(PharmacyStore $store, PharmacyBatch $batch, int $quantity, User $user, string $type = PharmacyStockTransaction::TYPE_PURCHASE_RECEIPT, ?object $reference = null): PharmacyStock
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Received quantity must be at least 1.']);
        }

        return DB::transaction(fn () => $this->credit($store, $batch->medication, $batch, $quantity, $type, $user, null, $reference));
    }

    public function adjust(PharmacyStore $store, PharmacyBatch $batch, int $quantityDelta, string $type, string $reason, User $user): PharmacyStock
    {
        if ($quantityDelta === 0) {
            throw ValidationException::withMessages(['quantity' => 'Adjustment quantity cannot be zero.']);
        }

        return DB::transaction(function () use ($store, $batch, $quantityDelta, $type, $reason, $user) {
            return $quantityDelta > 0
                ? $this->credit($store, $batch->medication, $batch, $quantityDelta, $type, $user, $reason)
                : $this->debit($store, $batch->medication, $batch, abs($quantityDelta), $type, $user, $reason);
        });
    }

    public function credit(PharmacyStore $store, PharmacyMedication $medication, PharmacyBatch $batch, int $quantity, string $type, User $user, ?string $reason = null, ?object $reference = null): PharmacyStock
    {
        $stock = $this->lockedStockRow($store, $medication, $batch);
        $balanceAfter = $stock->quantity_available + $quantity;
        $stock->update(['quantity_available' => $balanceAfter]);

        $this->recordTransaction($store, $medication, $batch, $type, 'in', $quantity, $balanceAfter, $user, $reason, $reference);

        return $stock->refresh();
    }

    public function debit(PharmacyStore $store, PharmacyMedication $medication, PharmacyBatch $batch, int $quantity, string $type, User $user, ?string $reason = null, ?object $reference = null): PharmacyStock
    {
        $stock = $this->lockedStockRow($store, $medication, $batch);

        if ($stock->quantity_available < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock available for this batch.']);
        }

        $balanceAfter = $stock->quantity_available - $quantity;
        $stock->update(['quantity_available' => $balanceAfter]);

        $this->recordTransaction($store, $medication, $batch, $type, 'out', $quantity, $balanceAfter, $user, $reason, $reference);

        return $stock->refresh();
    }

    private function lockedStockRow(PharmacyStore $store, PharmacyMedication $medication, PharmacyBatch $batch): PharmacyStock
    {
        try {
            PharmacyStock::query()->firstOrCreate([
                'store_id' => $store->id,
                'medication_id' => $medication->id,
                'batch_id' => $batch->id,
            ], ['quantity_available' => 0]);
        } catch (\Illuminate\Database\QueryException) {
            // Lost a race to create the stock row — another concurrent transaction already
            // inserted it; fall through to the locked read below, which will now find it.
        }

        return PharmacyStock::query()
            ->where('store_id', $store->id)
            ->where('medication_id', $medication->id)
            ->where('batch_id', $batch->id)
            ->lockForUpdate()
            ->first();
    }

    private function recordTransaction(PharmacyStore $store, PharmacyMedication $medication, PharmacyBatch $batch, string $type, string $direction, int $quantity, int $balanceAfter, User $user, ?string $reason, ?object $reference): void
    {
        PharmacyStockTransaction::create([
            'store_id' => $store->id,
            'medication_id' => $medication->id,
            'batch_id' => $batch->id,
            'type' => $type,
            'direction' => $direction,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'performed_by' => $user->id,
            'reason' => $reason,
        ]);
    }
}
