<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\ProcurementContract;

/** What to expect an item to cost per PURCHASE unit: a running contract first, then the last paid cost, then the standard cost. */
class ProcurementPricing
{
    public function unitPrice(InventoryItem $item, ?int $supplierId = null, ?string $on = null): string
    {
        $on ??= now()->toDateString();
        if ($supplierId) {
            $contract = ProcurementContract::query()->where(['item_id' => $item->id, 'supplier_id' => $supplierId])->whereDate('valid_from', '<=', $on)
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $on))->orderByDesc('valid_from')->first();
            if ($contract) {
                return bcadd((string) $contract->unit_price, '0', 4);
            }
        }
        $perBase = bccomp((string) ($item->last_cost ?? 0), '0', 4) > 0 ? (string) $item->last_cost : (string) $item->standard_cost;

        return bcmul($perBase, (string) $item->purchase_to_base, 4);
    }

    public function contractPrice(InventoryItem $item, int $supplierId, ?string $on = null): ?string
    {
        $on ??= now()->toDateString();
        $contract = ProcurementContract::query()->where(['item_id' => $item->id, 'supplier_id' => $supplierId])->whereDate('valid_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $on))->orderByDesc('valid_from')->first();

        return $contract ? bcadd((string) $contract->unit_price, '0', 4) : null;
    }
}
