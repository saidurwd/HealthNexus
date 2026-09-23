<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStockCount;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * start() snapshots expected quantities from pharmacy_stock, recordCount() records the physical
 * count, complete() computes variance, and adjust() is a distinct, explicit step that applies the
 * variance to pharmacy_stock — a stock count never auto-adjusts.
 */
class PharmacyStockCountService
{
    public function __construct(private readonly PharmacyStockService $stock, private readonly PharmacyNumberGenerator $numbers) {}

    public function start(PharmacyStore $store, User $user): PharmacyStockCount
    {
        return DB::transaction(function () use ($store, $user) {
            $count = PharmacyStockCount::create([
                'company_id' => $store->company_id,
                'branch_id' => $store->branch_id,
                'store_id' => $store->id,
                'count_number' => $this->numbers->generate($store->company_id, $store->branch_id, 'CNT', 'CNT'),
                'status' => 'in_progress',
                'started_by' => $user->id,
                'started_at' => now(),
            ]);

            $stockRows = PharmacyStock::query()->where('store_id', $store->id)->where('quantity_available', '>', 0)->get();

            foreach ($stockRows as $stockRow) {
                $count->items()->create([
                    'medication_id' => $stockRow->medication_id,
                    'batch_id' => $stockRow->batch_id,
                    'expected_quantity' => $stockRow->quantity_available,
                ]);
            }

            return $count->load('items');
        });
    }

    /**
     * @param  array<int, array{stock_count_item_id:int, counted_quantity:int}>  $counts
     */
    public function recordCount(PharmacyStockCount $count, array $counts): PharmacyStockCount
    {
        if ($count->status !== 'in_progress') {
            throw ValidationException::withMessages(['count' => "This stock count is already '{$count->status}'."]);
        }

        return DB::transaction(function () use ($count, $counts) {
            $count->loadMissing('items');

            foreach ($counts as $entry) {
                $item = $count->items->firstWhere('id', $entry['stock_count_item_id']);

                if (! $item) {
                    continue;
                }

                $countedQuantity = (int) $entry['counted_quantity'];
                $item->update([
                    'counted_quantity' => $countedQuantity,
                    'variance' => $countedQuantity - $item->expected_quantity,
                ]);
            }

            return $count->refresh()->load('items');
        });
    }

    public function complete(PharmacyStockCount $count, User $user): PharmacyStockCount
    {
        if ($count->status !== 'in_progress') {
            throw ValidationException::withMessages(['count' => "This stock count is already '{$count->status}'."]);
        }

        $count->loadMissing('items');

        if ($count->items->contains(fn ($item) => $item->counted_quantity === null)) {
            throw ValidationException::withMessages(['count' => 'Every item must be counted before this stock count can be completed.']);
        }

        $count->update(['status' => 'completed', 'completed_by' => $user->id, 'completed_at' => now()]);

        return $count->refresh()->load('items');
    }

    public function applyAdjustments(PharmacyStockCount $count, User $user): PharmacyStockCount
    {
        if ($count->status !== 'completed') {
            throw ValidationException::withMessages(['count' => 'Only a completed stock count can have its variances applied.']);
        }

        return DB::transaction(function () use ($count, $user) {
            $count->loadMissing('store', 'items.medication', 'items.batch');

            foreach ($count->items->where('variance', '!=', 0)->where('is_adjusted', false) as $item) {
                $this->stock->adjust(
                    $count->store,
                    $item->batch,
                    $item->variance,
                    PharmacyStockTransaction::TYPE_CORRECTION,
                    "Stock count {$count->count_number} variance adjustment",
                    $user,
                );

                $item->update(['is_adjusted' => true]);
            }

            $count->update(['status' => 'adjusted']);

            return $count->refresh()->load('items');
        });
    }
}
