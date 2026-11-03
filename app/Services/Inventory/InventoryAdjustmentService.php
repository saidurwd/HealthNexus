<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryAdjustment;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Any stock change that no document explains (damage, expiry write-off, found stock, count variance) needs a second person (guideline 12.2 rule 5). */
class InventoryAdjustmentService
{
    public function __construct(private readonly InventoryInputGuard $guard, private readonly InventoryStockService $stock, private readonly InventoryPolicy $policy, private readonly AuditLogger $audit) {}

    public function request(User $by, InventoryStore $store, InventoryItem $item, int $batchId, float|int|string $quantity, string $reasonCode, string $reason, ?int $countId = null): InventoryAdjustment
    {
        $this->guard->sameOrganization($store, $item);
        $quantity = bcadd((string) $quantity, '0', 4);
        if (bccomp($quantity, '0', 4) === 0) {
            throw ValidationException::withMessages(['quantity' => 'An adjustment needs a quantity (negative to remove stock).']);
        }
        if (! in_array($reasonCode, (array) $this->policy->get('adjustment_reason_codes'), true)) {
            throw ValidationException::withMessages(['reason_code' => 'Not a known reason.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why.']);
        }
        if ($reasonCode === 'expired' && bccomp($quantity, '0', 4) > 0) {
            throw ValidationException::withMessages(['reason_code' => 'An expiry write-off removes stock.']);
        }

        return InventoryAdjustment::create(['organization_id' => $store->organization_id, 'store_id' => $store->id, 'item_id' => $item->id, 'batch_id' => $batchId, 'quantity' => $quantity, 'reason_code' => $reasonCode, 'reason' => $reason, 'count_id' => $countId, 'requested_by' => $by->id]);
    }

    /** @param  bool  $sameRequesterOk  a count variance is approved by the count approver, who is not the counter, so the rule is checked there */
    public function approve(InventoryAdjustment $adjustment, User $by, bool $sameRequesterOk = false): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $by, $sameRequesterOk) {
            $adjustment = InventoryAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($adjustment->status !== 'requested') {
                throw ValidationException::withMessages(['status' => "This adjustment is already {$adjustment->status}."]);
            }
            if (! $sameRequesterOk && $adjustment->requested_by === $by->id) {
                throw ValidationException::withMessages(['approval' => 'A second person must approve a stock adjustment.']);
            }
            $store = $adjustment->store;
            $item = InventoryItem::findOrFail($adjustment->item_id);
            $key = "ADJ:{$adjustment->id}";
            $cost = (string) ($adjustment->batch_id > 0 ? ($item->batches()->find($adjustment->batch_id)->unit_cost ?? $item->standard_cost) : $item->standard_cost);
            if (bccomp((string) $adjustment->quantity, '0', 4) > 0) {
                $this->stock->receive($store, $item, (int) $adjustment->batch_id, (string) $adjustment->quantity, 'adjustment_gain', $key, $cost, InventoryAdjustment::class, $adjustment->id, $by, $adjustment->reason);
            } else {
                $txn = match ($adjustment->reason_code) {
                    'expired' => 'expiry_writeoff', 'damage' => 'wastage', default => 'adjustment_loss'
                };
                $this->stock->take($store, $item, (int) $adjustment->batch_id, ltrim((string) $adjustment->quantity, '-'), $txn, $key, $cost, InventoryAdjustment::class, $adjustment->id, $by, $adjustment->reason);
            }
            $adjustment->update(['status' => 'approved', 'decided_by' => $by->id, 'decided_at' => now()]);
            $this->audit->log('STOCK_ADJUSTED', InventoryAdjustment::class, $adjustment->id, null, ['item' => $item->code, 'quantity' => (string) $adjustment->quantity, 'reason' => $adjustment->reason_code]);

            return $adjustment->refresh();
        });
    }

    public function reject(InventoryAdjustment $adjustment, User $by, string $reason): InventoryAdjustment
    {
        if ($adjustment->status !== 'requested') {
            throw ValidationException::withMessages(['status' => "This adjustment is already {$adjustment->status}."]);
        }
        $adjustment->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'reason' => Str::limit($adjustment->reason.' · Rejected: '.$reason, 300, '')]);

        return $adjustment->refresh();
    }
}
