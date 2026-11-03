<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Today's stock position by store: how many items are stocked and how many are out. One row is added per day the loader runs, so the history builds up from the first load. */
class StockDailyFact extends Loader
{
    public function key(): string
    {
        return 'fact_stock_daily';
    }

    public function label(): string
    {
        return 'Stock-outs';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'fact_stock_daily';
    }

    protected function uniqueBy(): array
    {
        return ['date_key', 'organization_id', 'store_id'];
    }

    public function load(?string $watermark): array
    {
        $day = CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'));
        $perItem = DB::table('inventory_stock')->groupBy('organization_id', 'store_id', 'item_id')->selectRaw('organization_id, store_id, item_id, sum(quantity_on_hand - quantity_reserved) as available')->get();
        $stores = [];
        foreach ($perItem as $r) {
            $k = $r->organization_id.':'.$r->store_id;
            $stores[$k] ??= ['date_key' => (int) $day->format('Ymd'), 'organization_id' => $r->organization_id, 'store_id' => $r->store_id, 'items_tracked' => 0, 'items_out' => 0, 'loaded_at' => now()->toDateTimeString()];
            $stores[$k]['items_tracked']++;
            (float) $r->available <= 0 && $stores[$k]['items_out']++;
        }
        $written = $this->warehouse->upsert('fact_stock_daily', array_values($stores), $this->uniqueBy());

        return ['read' => $perItem->count(), 'written' => $written, 'watermark' => $day->toDateString()];
    }
}
