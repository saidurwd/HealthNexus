<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStore;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * FEFO (First-Expiry-First-Out) batch allocation. Orders eligible batches (not expired, not
 * quarantined) by expiry_date ASC and allocates greedily until the requested quantity is
 * satisfied. Errors — never silently partial-allocates — when stock is insufficient, so callers
 * always know the true state before touching pharmacy_stock.
 */
class PharmacyBatchSelectionService
{
    /**
     * @return Collection<int, array{stock: PharmacyStock, quantity: int}>
     */
    public function selectForDispense(PharmacyStore $store, PharmacyMedication $medication, int $quantityNeeded): Collection
    {
        if ($quantityNeeded < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity to dispense must be at least 1.']);
        }

        $eligibleStock = PharmacyStock::query()
            ->where('pharmacy_stock.store_id', $store->id)
            ->where('pharmacy_stock.medication_id', $medication->id)
            ->where('pharmacy_stock.quantity_available', '>', 0)
            ->join('pharmacy_batches', 'pharmacy_batches.id', '=', 'pharmacy_stock.batch_id')
            ->where('pharmacy_batches.is_quarantined', false)
            ->where('pharmacy_batches.expiry_date', '>', now()->toDateString())
            ->orderBy('pharmacy_batches.expiry_date', 'asc')
            ->select('pharmacy_stock.*')
            ->with('batch')
            ->get();

        $remaining = $quantityNeeded;
        $allocations = collect();

        foreach ($eligibleStock as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $stock->quantity_available);
            $allocations->push(['stock' => $stock, 'quantity' => $take]);
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock: {$quantityNeeded} requested, ".($quantityNeeded - $remaining).' available across eligible batches.',
            ]);
        }

        return $allocations;
    }
}
