<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryCount;
use App\Models\Inventory\InventoryCountLine;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock counts (S09). The sheet freezes what the system expects; people enter what they find; the difference becomes an adjustment only when a
 * different person approves the count. Movement during a count should be paused by the store — a variance is posted as the difference at approval.
 */
class InventoryCountService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly InventoryAdjustmentService $adjustments, private readonly InventoryPolicy $policy) {}

    /** @param  list<int>|null  $itemIds  null = every item held (a full count); a list = a cycle count of those items */
    public function start(User $by, InventoryStore $store, ?array $itemIds = null): InventoryCount
    {
        $positions = InventoryStock::query()->where('store_id', $store->id)->when($itemIds !== null, fn ($q) => $q->whereIn('item_id', $itemIds))
            ->where(fn ($q) => $q->where('quantity_on_hand', '!=', 0)->orWhere('quantity_reserved', '!=', 0))->get();
        if ($positions->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Nothing is held for those items; there is nothing to count.']);
        }

        return DB::transaction(function () use ($by, $store, $itemIds, $positions) {
            $count = InventoryCount::create(['organization_id' => $store->organization_id, 'store_id' => $store->id, 'number' => $this->numbers->next('count', $store->organization_id, $by), 'kind' => $itemIds === null ? 'full' : 'cycle', 'started_by' => $by->id]);
            foreach ($positions as $position) {
                $count->lines()->create(['item_id' => $position->item_id, 'batch_id' => $position->batch_id, 'system_quantity' => $position->quantity_on_hand]);
            }

            return $count->refresh();
        });
    }

    public function enter(InventoryCountLine $line, float|int|string $counted, ?string $reason = null): InventoryCountLine
    {
        $count = InventoryCount::findOrFail($line->count_id);
        if ($count->status !== 'counting') {
            throw ValidationException::withMessages(['status' => 'This count is no longer open.']);
        }
        if (bccomp((string) $counted, '0', 4) < 0) {
            throw ValidationException::withMessages(['counted' => 'A count cannot be negative.']);
        }
        $line->update(['counted_quantity' => $counted, 'variance' => bcsub((string) $counted, (string) $line->system_quantity, 4), 'reason' => $reason]);

        return $line->refresh();
    }

    public function submit(InventoryCount $count, User $by): InventoryCount
    {
        if ($count->status !== 'counting') {
            throw ValidationException::withMessages(['status' => 'This count is no longer open.']);
        }
        $lines = $count->lines()->get();
        if ($lines->contains(fn ($l) => $l->counted_quantity === null)) {
            throw ValidationException::withMessages(['lines' => 'Every line must be counted (enter 0 if none was found).']);
        }
        $noteAt = (string) $this->policy->get('count_variance_note_percent');
        foreach ($lines as $line) {
            if (bccomp((string) $line->variance, '0', 4) === 0 || $line->reason) {
                continue;
            }
            $base = bccomp((string) $line->system_quantity, '0', 4) > 0 ? (string) $line->system_quantity : '1';
            $percent = bcmul(bcdiv(ltrim((string) $line->variance, '-'), $base, 6), '100', 2);
            if (bccomp($percent, $noteAt, 2) >= 0) {
                throw ValidationException::withMessages(['reason' => 'A difference of '.$percent.'% needs a reason on the line.']);
            }
        }
        $count->update(['status' => 'submitted']);

        return $count->refresh();
    }

    /** Posts every variance as an adjustment. The approver cannot be the person who started the count. */
    public function approve(InventoryCount $count, User $by): InventoryCount
    {
        return DB::transaction(function () use ($count, $by) {
            $count = InventoryCount::query()->lockForUpdate()->findOrFail($count->id);
            if ($count->status !== 'submitted') {
                throw ValidationException::withMessages(['status' => 'Only a submitted count can be approved.']);
            }
            if ($count->started_by === $by->id) {
                throw ValidationException::withMessages(['approval' => 'A second person must approve the count.']);
            }
            foreach ($count->lines()->get() as $line) {
                if (bccomp((string) $line->variance, '0', 4) === 0) {
                    continue;
                }
                $adjustment = $this->adjustments->request($count->starter ?? $by, $count->store, InventoryItem::findOrFail($line->item_id), (int) $line->batch_id, (string) $line->variance, 'count_variance', "Count {$count->number}: ".($line->reason ?: 'variance'), $count->id);
                $this->adjustments->approve($adjustment, $by, true);
            }
            $count->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now()]);

            return $count->refresh();
        });
    }

    public function reject(InventoryCount $count, User $by): InventoryCount
    {
        if ($count->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Only a submitted count can be rejected.']);
        }
        $count->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now()]);

        return $count->refresh();
    }
}
