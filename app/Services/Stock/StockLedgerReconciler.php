<?php

namespace App\Services\Stock;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Finds projections that no longer equal the ledger (H-06 done-when: ledger sums equal on-hand for every pharmacy batch). */
class StockLedgerReconciler
{
    /** @return Collection<int, object{store_id:int,item_id:int,batch_id:int,on_hand:int,ledger:float}> */
    public function pharmacyDrift(?int $organizationId = null): Collection
    {
        return DB::table('pharmacy_stock as p')
            ->join('pharmacy_stores as s', 's.id', '=', 'p.store_id')
            ->leftJoin('v_stock_on_hand_all_stores as v', function ($j) {
                $j->on('v.store_id', '=', 'p.store_id')->on('v.item_id', '=', 'p.medication_id')->on('v.batch_id', '=', 'p.batch_id')
                    ->where('v.store_kind', '=', 'pharmacy');
            })
            ->when($organizationId, fn ($q) => $q->where('s.organization_id', $organizationId))
            ->whereRaw('p.quantity_available <> COALESCE(v.on_hand, 0)')
            ->select('p.store_id', 'p.medication_id as item_id', 'p.batch_id', 'p.quantity_available as on_hand', DB::raw('COALESCE(v.on_hand, 0) as ledger'))
            ->get();
    }
}
