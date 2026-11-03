<?php

namespace App\Services\Finance;

use App\Models\Finance\Cursor;
use App\Models\Finance\Posting;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementGoodsReceipt;
use App\Models\Inventory\ProcurementGrnLine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock and purchasing in the books. A posted goods receipt becomes inventory against "goods received, not yet invoiced"; every other stock movement (issues, dispensing, returns, wastage,
 * expiry, adjustments) is valued from the append-only stock ledger once the day is over and summed into one journal per day and kind of movement.
 */
class StockPostingService
{
    /** movement type => 'expense' (consumed: cost of use), 'adjustment' (loss or gain) */
    private const KINDS = ['issue' => 'expense', 'dispense' => 'expense', 'return' => 'expense', 'wastage' => 'adjustment', 'expiry_writeoff' => 'adjustment', 'adjustment_gain' => 'adjustment', 'adjustment_loss' => 'adjustment', 'recall' => 'adjustment'];

    public function __construct(private readonly ChartService $chart, private readonly JournalService $journals, private readonly FinancePolicy $policy) {}

    private function inventoryAccount(int $org, string $storeKind): int
    {
        return $this->chart->accountFor($org, 'inventory', $storeKind === 'pharmacy' ? 'pharmacy' : null)->id;
    }

    /** @return int goods receipts posted */
    public function postReceipts(int $organizationId): int
    {
        $n = 0;
        $done = Posting::query()->where(['organization_id' => $organizationId, 'source_type' => 'grn', 'event_key' => 'grn.posted'])->whereIn('status', ['posted', 'skipped'])->pluck('source_id')->flip();
        foreach (ProcurementGoodsReceipt::query()->where('organization_id', $organizationId)->where('status', 'posted')->orderBy('id')->limit($this->policy->int('posting.batch', 200))->get() as $grn) {
            if ($done->has((string) $grn->id)) {
                continue;
            }
            $key = ['organization_id' => $organizationId, 'source_type' => 'grn', 'source_id' => (string) $grn->id, 'event_key' => 'grn.posted'];
            try {
                $journal = DB::transaction(function () use ($grn, $organizationId) {
                    $value = '0.00';
                    foreach (ProcurementGrnLine::query()->where('goods_receipt_id', $grn->id)->get() as $l) {
                        $value = bcadd($value, bcmul((string) $l->accepted_quantity, (string) $l->unit_cost, 2), 2);
                    }
                    if (bccomp($value, '0', 2) <= 0) {
                        return null;
                    }
                    $kind = (string) InventoryStore::query()->whereKey($grn->store_id)->value('kind');

                    return $this->journals->postAuto($organizationId, ['date' => ($grn->posted_at ?? $grn->received_on)->toDateString(), 'memo' => 'Goods received '.$grn->number, 'reference' => $grn->delivery_note, 'source_type' => 'grn', 'source_id' => (string) $grn->id, 'event_key' => 'grn.posted'], [
                        ['account' => $this->inventoryAccount($organizationId, $kind), 'debit' => $value, 'credit' => '0'], ['account' => $this->chart->accountFor($organizationId, 'grni')->id, 'debit' => '0', 'credit' => $value],
                    ], null, $this->policy->bool('period.post_into_locked', false));
                });
                Posting::query()->updateOrCreate($key, ['status' => $journal ? 'posted' : 'skipped', 'journal_id' => $journal?->id, 'error' => null, 'last_attempt_at' => now()]);
                $journal && $n++;
            } catch (\Throwable $e) {
                Posting::query()->updateOrCreate($key, ['status' => 'failed', 'error' => mb_substr($e instanceof ValidationException ? implode(' ', array_merge(...array_values($e->errors()))) : $e->getMessage(), 0, 400), 'last_attempt_at' => now()]);
            }
        }

        return $n;
    }

    /**
     * Value the stock ledger rows since the last run (finished days only) and post them. One journal per day and movement type.
     *
     * @return array{journals:int,rows:int,unvalued:int}
     */
    public function postMovements(int $organizationId, ?string $upToDate = null): array
    {
        $cursor = Cursor::query()->firstOrCreate(['organization_id' => $organizationId, 'name' => 'stock_ledger'], ['value' => 0]);
        $end = ($upToDate ? CarbonImmutable::parse($upToDate) : now()->startOfDay())->toDateTimeString(); // days that are not over are left for tomorrow
        $rows = DB::table('stock_ledger as s')->leftJoin('inventory_items as i', function ($j) {
            $j->on('i.id', '=', 's.item_id')->where('s.store_kind', '<>', 'pharmacy');
        })->where('s.organization_id', $organizationId)->where('s.id', '>', $cursor->value)->where('s.performed_at', '<', $end)->whereIn('s.txn_type', array_keys(self::KINDS))->orderBy('s.id')->limit(5000)
            ->get(['s.id', 's.store_kind', 's.quantity', 's.unit_cost', 's.txn_type', 's.performed_at', 'i.standard_cost']);
        if ($rows->isEmpty()) {
            return ['journals' => 0, 'rows' => 0, 'unvalued' => 0];
        }
        $groups = [];
        $unvalued = 0;
        foreach ($rows as $r) {
            $cost = $r->unit_cost ?? $r->standard_cost;
            if ($cost === null || bccomp((string) $cost, '0', 4) === 0) {
                $unvalued++;

                continue;
            }
            $value = bcmul((string) $r->quantity, (string) $cost, 2); // signed: negative = stock went down
            $k = CarbonImmutable::parse($r->performed_at, 'UTC')->setTimezone((string) config('finance.timezone', 'Asia/Dhaka'))->toDateString().'|'.$r->txn_type.'|'.($r->store_kind === 'pharmacy' ? 'pharmacy' : 'general');
            $groups[$k] = bcadd($groups[$k] ?? '0.00', $value, 2);
        }
        $made = 0;
        DB::transaction(function () use ($groups, $organizationId, $rows, $cursor, &$made) {
            foreach ($groups as $k => $net) {
                [$date, $type, $store] = explode('|', $k);
                if (bccomp($net, '0', 2) === 0) {
                    continue;
                }
                $inv = $this->inventoryAccount($organizationId, $store);
                $counter = $this->chart->accountFor($organizationId, self::KINDS[$type] === 'expense' ? 'stock_expense' : 'stock_adjustment')->id;
                $abs = ltrim($net, '-');
                $down = bccomp($net, '0', 2) < 0;
                $lines = $down ? [['account' => $counter, 'debit' => $abs, 'credit' => '0'], ['account' => $inv, 'debit' => '0', 'credit' => $abs]] : [['account' => $inv, 'debit' => $abs, 'credit' => '0'], ['account' => $counter, 'debit' => '0', 'credit' => $abs]];
                $this->journals->postAuto($organizationId, ['date' => $date, 'memo' => 'Stock: '.str_replace('_', ' ', $type).' ('.$store.') '.$date, 'source_type' => 'stock_day', 'source_id' => $k.'#'.$rows->first()->id, 'event_key' => 'valued'], $lines, null, $this->policy->bool('period.post_into_locked', false));
                $made++;
            }
            $cursor->update(['value' => $rows->last()->id]);
        });

        return ['journals' => $made, 'rows' => $rows->count(), 'unvalued' => $unvalued];
    }

    /** Stock value in the books against the value of stock on hand (cost x quantity) from the stock tables. */
    public function stockValueCheck(int $organizationId): array
    {
        $general = (string) DB::table('stock_ledger as s')->join('inventory_items as i', 'i.id', '=', 's.item_id')->where('s.organization_id', $organizationId)->where('s.store_kind', '<>', 'pharmacy')->selectRaw('coalesce(sum(s.quantity * coalesce(s.unit_cost, i.standard_cost, 0)), 0) as v')->value('v');

        return ['stock_ledger_value_general' => bcadd($general, '0', 2), 'items' => InventoryItem::query()->where('organization_id', $organizationId)->count()];
    }
}
