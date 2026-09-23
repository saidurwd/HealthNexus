<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyReturn;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Credits stock back only if a line is explicitly marked returnable-to-stock at verification —
 * never automatic (a returned medication may be damaged/opened/otherwise unfit for re-dispensing).
 */
class PharmacyReturnService
{
    public function __construct(
        private readonly PharmacyNumberGenerator $numbers,
        private readonly PharmacyStockService $stock,
    ) {}

    /**
     * @param  array<int, array{dispensing_item_id:int, quantity_returned:int}>  $items
     */
    public function request(PharmacyDispensing $dispensing, array $items, string $reason, User $user): PharmacyReturn
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'At least one item must be selected for return.']);
        }

        return DB::transaction(function () use ($dispensing, $items, $reason, $user) {
            $return = PharmacyReturn::create([
                'company_id' => $dispensing->company_id,
                'branch_id' => $dispensing->branch_id,
                'dispensing_id' => $dispensing->id,
                'store_id' => $dispensing->store_id,
                'return_number' => $this->numbers->generateReturnNumber($dispensing->company_id, $dispensing->branch_id),
                'status' => 'requested',
                'reason' => $reason,
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                $return->items()->create([
                    'dispensing_item_id' => $item['dispensing_item_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'returnable_to_stock' => false,
                ]);
            }

            return $return->load('items');
        });
    }

    /**
     * @param  array<int, array{return_item_id:int, returnable_to_stock:bool}>  $decisions
     */
    public function verify(PharmacyReturn $return, array $decisions, User $user): PharmacyReturn
    {
        if ($return->status !== 'requested') {
            throw ValidationException::withMessages(['return' => "This return is already '{$return->status}'."]);
        }

        return DB::transaction(function () use ($return, $decisions, $user) {
            $return->loadMissing('store', 'items.dispensingItem.medication', 'items.dispensingItem.batch');

            foreach ($decisions as $decision) {
                $item = $return->items->firstWhere('id', $decision['return_item_id']);

                if (! $item) {
                    continue;
                }

                $item->update(['returnable_to_stock' => (bool) $decision['returnable_to_stock']]);

                if ($item->returnable_to_stock) {
                    $dispensingItem = $item->dispensingItem;

                    if (! $dispensingItem->batch) {
                        throw ValidationException::withMessages(['return' => 'Cannot return this item to stock — its original batch is unknown.']);
                    }

                    $this->stock->credit(
                        $return->store,
                        $dispensingItem->medication,
                        $dispensingItem->batch,
                        $item->quantity_returned,
                        PharmacyStockTransaction::TYPE_RETURN,
                        $user,
                        null,
                        $return,
                    );
                }
            }

            $return->update([
                'status' => 'verified',
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            return $return->refresh()->load('items');
        });
    }
}
