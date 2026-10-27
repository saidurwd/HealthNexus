<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Finds projections that no longer equal the ledger (S03 done-when: balance equals the ledger sum), and can rebuild them from it. */
class InventoryLedgerReconciler
{
    /** @return Collection<int, object{store_id:int,item_id:int,batch_id:int,on_hand:string,ledger:string}> */
    public function drift(?int $organizationId = null): Collection
    {
        $ledger = DB::table('stock_ledger')->where('store_kind', 'inventory')->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->groupBy('store_id', 'item_id', DB::raw('COALESCE(batch_id, 0)'))->selectRaw('store_id, item_id, COALESCE(batch_id, 0) as batch_id, SUM(quantity) as q')->get()->keyBy(fn ($r) => "{$r->store_id}:{$r->item_id}:{$r->batch_id}");
        $rows = collect();
        $seen = [];
        InventoryStock::query()->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))->orderBy('id')->each(function (InventoryStock $s) use ($ledger, &$rows, &$seen) {
            $key = "{$s->store_id}:{$s->item_id}:{$s->batch_id}";
            $seen[$key] = true;
            $q = (string) ($ledger[$key]->q ?? 0);
            if (bccomp((string) $s->quantity_on_hand, $q, 4) !== 0) {
                $rows->push((object) ['store_id' => $s->store_id, 'item_id' => $s->item_id, 'batch_id' => $s->batch_id, 'on_hand' => (string) $s->quantity_on_hand, 'ledger' => bcadd($q, '0', 4)]);
            }
        });
        foreach ($ledger as $key => $row) {
            if (! isset($seen[$key]) && bccomp((string) $row->q, '0', 4) !== 0) {
                $rows->push((object) ['store_id' => $row->store_id, 'item_id' => $row->item_id, 'batch_id' => (int) $row->batch_id, 'on_hand' => '0.0000', 'ledger' => bcadd((string) $row->q, '0', 4)]);
            }
        }

        return $rows;
    }

    /** Sets every drifting projection row to what the ledger says. @return int rows corrected */
    public function rebuild(?int $organizationId = null): int
    {
        $fixed = 0;
        foreach ($this->drift($organizationId) as $d) {
            $row = InventoryStock::query()->firstOrNew(['store_id' => $d->store_id, 'item_id' => $d->item_id, 'batch_id' => $d->batch_id]);
            $row->organization_id ??= DB::table('inventory_stores')->where('id', $d->store_id)->value('organization_id');
            $row->quantity_on_hand = $d->ledger;
            $row->save();
            $fixed++;
        }

        return $fixed;
    }
}
