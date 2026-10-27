<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementRequisition;
use App\Models\Stock\StockLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** What to buy next (S11): items at or under their reorder point, sized to the maximum, from real consumption — not a guess. */
class ReorderService
{
    public function __construct(private readonly InventoryPolicy $policy, private readonly RequisitionService $requisitions) {}

    /** @return list<array<string, mixed>> */
    public function suggestions(int $organizationId): array
    {
        $lookback = (int) $this->policy->get('reorder')['lookback_days'];
        $safety = (int) $this->policy->get('reorder')['safety_days'];
        // Transfers move stock between our own stores and are not consumption; only count 'issue'.
        $consumed = StockLedgerEntry::query()->where(['organization_id' => $organizationId, 'store_kind' => 'inventory', 'txn_type' => 'issue'])->where('performed_at', '>=', now()->subDays($lookback))->selectRaw('item_id, -SUM(quantity) as used')->groupBy('item_id')->pluck('used', 'item_id');
        $onHand = DB::table('inventory_stock as s')->join('inventory_stores as st', 'st.id', '=', 's.store_id')->where('s.organization_id', $organizationId)->where('st.kind', '!=', 'quarantine')->selectRaw('s.item_id, SUM(s.quantity_on_hand) as q')->groupBy('s.item_id')->pluck('q', 'item_id');
        $onOrder = DB::table('procurement_po_lines as l')->join('procurement_purchase_orders as o', 'o.id', '=', 'l.purchase_order_id')->join('inventory_items as i', 'i.id', '=', 'l.item_id')
            ->where('o.organization_id', $organizationId)->whereIn('o.status', ['approved', 'pending_approval', 'issued', 'partially_received'])->where('l.closed', false)
            ->selectRaw('l.item_id, SUM((l.quantity - l.received_quantity) * i.purchase_to_base) as q')->groupBy('l.item_id')->pluck('q', 'item_id');

        $rows = [];
        foreach (InventoryItem::query()->where(['organization_id' => $organizationId, 'status' => 'active'])->where('reorder_min', '>', 0)->orderBy('code')->get() as $item) {
            $daily = bcdiv((string) ($consumed[$item->id] ?? 0), (string) $lookback, 6);
            $point = bcadd((string) $item->reorder_min, bcmul($daily, (string) ($item->lead_time_days + $safety), 4), 4);
            $have = bcadd((string) ($onHand[$item->id] ?? 0), (string) ($onOrder[$item->id] ?? 0), 4);
            if (bccomp($have, $point, 4) > 0) {
                continue;
            }
            $target = bccomp((string) $item->reorder_max, $point, 4) > 0 ? (string) $item->reorder_max : bcmul($point, '2', 4);
            $buyBase = bcsub($target, $have, 4);
            $factor = (string) $item->purchase_to_base;
            $units = (string) ceil((float) bcdiv($buyBase, $factor, 6));
            $rows[] = [
                'item_id' => $item->id, 'code' => $item->code, 'name' => $item->name, 'on_hand' => bcadd((string) ($onHand[$item->id] ?? 0), '0', 2), 'on_order' => bcadd((string) ($onOrder[$item->id] ?? 0), '0', 2),
                'daily_use' => bcadd($daily, '0', 2), 'reorder_point' => bcadd($point, '0', 2), 'suggested_purchase_units' => $units, 'purchase_uom' => $item->purchase_uom ?? $item->base_uom,
            ];
        }

        return $rows;
    }

    /** @param  list<int>  $itemIds */
    public function raiseRequisition(User $by, InventoryStore $store, array $itemIds): ProcurementRequisition
    {
        $wanted = collect($this->suggestions($store->organization_id))->whereIn('item_id', $itemIds);

        return $this->requisitions->create($by, $store, $wanted->map(fn ($r) => ['item_id' => $r['item_id'], 'quantity' => $r['suggested_purchase_units']])->values()->all(), ['notes' => 'Raised from reorder suggestions']);
    }
}
