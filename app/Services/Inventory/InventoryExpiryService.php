<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryAdjustment;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryConsumption;
use App\Models\Inventory\InventoryIssueLine;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRecall;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\InventoryTransferLine;
use App\Models\Stock\StockLedgerEntry;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Expiry, quarantine and recall (S09). Blocked stock is moved to the quarantine store, never hidden; an expired batch can only leave through a write-off. */
class InventoryExpiryService
{
    public function __construct(
        private readonly InventoryStockService $stock,
        private readonly InventoryMasterService $masters,
        private readonly InventoryAdjustmentService $adjustments,
        private readonly InventoryPolicy $policy,
        private readonly AlertService $alerts,
    ) {}

    /** Stock that expires within the given days (or already has), soonest first. @return \Illuminate\Support\Collection<int, object> */
    public function risk(int $organizationId, int $days)
    {
        return DB::table('v_expiry_risk as r')->join('inventory_items as i', 'i.id', '=', 'r.item_id')->join('inventory_stores as s', 's.id', '=', 'r.store_id')
            ->where('r.organization_id', $organizationId)->where('s.kind', '!=', 'quarantine')->where('r.expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('r.expiry_date')->get(['r.*', 'i.code as item_code', 'i.name as item_name', 'i.base_uom', 's.name as store_name'])
            ->map(function ($row) {
                $row->days_left = (int) now()->startOfDay()->diffInDays(Carbon::parse($row->expiry_date), false);

                return $row;
            });
    }

    /**
     * Daily job: marks batches past their date as expired (blocked from issue), proposes the write-off for a second person to approve, and warns about what is
     * about to expire. Safe to run again: nothing is duplicated.
     *
     * @return array{expired: int, writeoffs_proposed: int, alerts: int}
     */
    public function sweep(int $organizationId, ?User $by = null, array $recipientUserIds = []): array
    {
        $expired = 0;
        $proposed = 0;
        InventoryBatch::query()->where('organization_id', $organizationId)->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString())->whereIn('status', ['active', 'quarantined', 'recalled'])->orderBy('id')->each(function (InventoryBatch $batch) use (&$expired, &$proposed, $by) {
            $batch->status === 'active' && $batch->update(['status' => 'expired', 'status_reason' => 'Past its expiry date']) && $expired++;
            $item = InventoryItem::find($batch->item_id);
            if (! $item || ! $by) {
                return;
            }
            InventoryStock::query()->where('batch_id', $batch->id)->where('quantity_on_hand', '>', 0)->with('store')->get()->each(function (InventoryStock $row) use ($item, $batch, $by, &$proposed) {
                $open = InventoryAdjustment::query()->where(['store_id' => $row->store_id, 'item_id' => $item->id, 'batch_id' => $batch->id, 'reason_code' => 'expired', 'status' => 'requested'])->exists();
                if (! $open) {
                    $this->adjustments->request($by, $row->store, $item, $batch->id, bcmul((string) $row->quantity_on_hand, '-1', 4), 'expired', "Batch {$batch->batch_no} expired {$batch->expiry_date->toDateString()}");
                    $proposed++;
                }
            });
        });

        $alerts = 0;
        $windows = (array) $this->policy->get('expiry_alert_days');
        foreach ($this->risk($organizationId, $windows ? max($windows) : 0)->where('days_left', '>=', 0) as $row) {
            $window = collect($windows)->sort()->first(fn ($w) => $row->days_left <= $w);
            if ($window === null) {
                continue;
            }
            $alert = $this->alerts->raise(new AlertRequest(
                organizationId: $organizationId, category: 'inventory_expiry', severity: $window <= 30 ? 'warning' : 'info', sourceModule: 'inventory',
                message: "{$row->item_name} batch {$row->batch_no} in {$row->store_name}: {$row->quantity_on_hand} {$row->base_uom} expire in {$row->days_left} day(s).",
                sourceType: InventoryBatch::class, sourceId: $row->batch_id, recipientUserIds: $recipientUserIds, ackRequired: false, dedupKey: "inv-expiry:{$row->batch_id}:{$row->store_id}:{$window}",
            ));
            $alert->wasRecentlyCreated && $alerts++;
        }

        return ['expired' => $expired, 'writeoffs_proposed' => $proposed, 'alerts' => $alerts];
    }

    /** Puts a batch on hold: everything held in the working stores moves to quarantine and cannot be issued. @return string quantity moved */
    public function quarantine(InventoryBatch $batch, User $by, string $reason, string $status = 'quarantined', string $outTxn = 'quarantine_out'): string
    {
        if ($batch->status === 'quarantined' && $status === 'quarantined') {
            throw ValidationException::withMessages(['batch' => 'That batch is already on hold.']);
        }

        return DB::transaction(function () use ($batch, $by, $reason, $status, $outTxn) {
            $item = InventoryItem::findOrFail($batch->item_id);
            $qrn = $this->masters->quarantineStore($batch->organization_id);
            $moved = '0.0000';
            $rows = InventoryStock::query()->where('batch_id', $batch->id)->where('quantity_on_hand', '>', 0)->where('store_id', '!=', $qrn->id)->with('store')->lockForUpdate()->get();
            $batch->update(['status' => $status, 'status_reason' => Str::limit($reason, 300, '')]);
            foreach ($rows as $row) {
                $qty = (string) $row->quantity_on_hand;
                $this->stock->take($row->store, $item, $batch->id, $qty, $outTxn, "QRN:{$batch->id}:{$row->store_id}:{$status}:out", (string) $batch->unit_cost, InventoryBatch::class, $batch->id, $by, $reason);
                $this->stock->receive($qrn, $item, $batch->id, $qty, 'quarantine_in', "QRN:{$batch->id}:{$row->store_id}:{$status}:in", (string) $batch->unit_cost, InventoryBatch::class, $batch->id, $by, $reason);
                $moved = bcadd($moved, $qty, 4);
            }

            return $moved;
        });
    }

    /** Clears a hold and puts the stock back where it came from. */
    public function release(InventoryBatch $batch, User $by, string $note): string
    {
        if ($batch->status !== 'quarantined') {
            throw ValidationException::withMessages(['batch' => 'Only a batch on hold can be released; a recalled or expired batch cannot be put back.']);
        }

        return DB::transaction(function () use ($batch, $by, $note) {
            $item = InventoryItem::findOrFail($batch->item_id);
            $qrn = $this->masters->quarantineStore($batch->organization_id);
            $batch->update(['status' => 'active', 'status_reason' => null]);
            $origins = StockLedgerEntry::query()->where(['store_kind' => 'inventory', 'item_id' => $item->id, 'batch_id' => $batch->id, 'txn_type' => 'quarantine_out'])->where('store_id', '!=', $qrn->id)->selectRaw('store_id, -SUM(quantity) as q')->groupBy('store_id')->get();
            $inQrn = $this->stock->onHand($qrn, $item, $batch->id);
            $moved = '0.0000';
            foreach ($origins as $origin) {
                $qty = bccomp((string) $origin->getAttribute('q'), $inQrn, 4) < 0 ? (string) $origin->getAttribute('q') : $inQrn;
                if (bccomp($qty, '0', 4) <= 0) {
                    continue;
                }
                $store = InventoryStore::findOrFail($origin->store_id);
                $stamp = now()->format('YmdHis');
                $this->stock->take($qrn, $item, $batch->id, $qty, 'quarantine_out', "QRN:{$batch->id}:{$origin->store_id}:rel:{$stamp}:out", (string) $batch->unit_cost, InventoryBatch::class, $batch->id, $by, $note);
                $this->stock->receive($store, $item, $batch->id, $qty, 'quarantine_in', "QRN:{$batch->id}:{$origin->store_id}:rel:{$stamp}:in", (string) $batch->unit_cost, InventoryBatch::class, $batch->id, $by, $note);
                $inQrn = bcsub($inQrn, $qty, 4);
                $moved = bcadd($moved, $qty, 4);
            }

            return $moved;
        });
    }

    /** A supplier or regulator recall: every batch of the item (or the named batch) is blocked and moved to quarantine. */
    public function recall(InventoryItem $item, ?string $batchNo, string $reason, User $by): InventoryRecall
    {
        $batches = InventoryBatch::query()->where('item_id', $item->id)->when($batchNo, fn ($q) => $q->where('batch_no', $batchNo))->whereIn('status', ['active', 'quarantined', 'expired'])->get();
        if ($batches->isEmpty()) {
            throw ValidationException::withMessages(['batch' => 'No such batch of that item.']);
        }

        return DB::transaction(function () use ($item, $batchNo, $reason, $by, $batches) {
            $blocked = '0.0000';
            foreach ($batches as $batch) {
                $blocked = bcadd($blocked, $this->quarantine($batch, $by, 'Recalled: '.$reason, 'recalled', 'recall'), 4);
            }

            return InventoryRecall::create(['organization_id' => $item->organization_id, 'item_id' => $item->id, 'batch_no' => $batchNo, 'reason' => $reason, 'quantity_blocked' => $blocked, 'initiated_by' => $by->id]);
        });
    }

    /** Where a batch went: issues, consumption and transfers, so the recall can be followed to the people who hold or used it. @return array<string, mixed> */
    public function trace(InventoryBatch $batch): array
    {
        return [
            'issued' => InventoryIssueLine::query()->where('batch_id', $batch->id)->join('inventory_issues as i', 'i.id', '=', 'inventory_issue_lines.issue_id')->join('departments as d', 'd.id', '=', 'i.department_id')
                ->selectRaw('d.name as department, SUM(inventory_issue_lines.quantity) as quantity')->groupBy('d.name')->get()->all(),
            'consumed' => InventoryConsumption::query()->where('batch_id', $batch->id)->selectRaw('COUNT(DISTINCT patient_id) as patients, SUM(quantity) as quantity')->first()?->toArray(),
            'transferred' => InventoryTransferLine::query()->where('batch_id', $batch->id)->count(),
            'on_hand' => InventoryStock::query()->where('batch_id', $batch->id)->where('quantity_on_hand', '>', 0)->with('store:id,name')->get()->map(fn ($r) => ['store' => $r->store->name ?? '', 'quantity' => (string) $r->quantity_on_hand])->all(),
        ];
    }
}
