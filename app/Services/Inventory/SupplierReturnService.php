<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Inventory\ProcurementSupplierReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Sending goods back to a supplier (S10): rejected deliveries, recalled or expired stock. Stock leaves on dispatch; the credit note closes it. */
class SupplierReturnService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly InventoryStockService $stock) {}

    /** @param  list<array{item_id: int, batch_id?: int, quantity: float|int|string}>  $lines  base units */
    public function create(User $by, ProcurementSupplier $supplier, InventoryStore $store, array $lines, string $reason, ?int $goodsReceiptId = null): ProcurementSupplierReturn
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why the goods go back.']);
        }

        return DB::transaction(function () use ($by, $supplier, $store, $lines, $reason, $goodsReceiptId) {
            $return = ProcurementSupplierReturn::create(['organization_id' => $store->organization_id, 'supplier_id' => $supplier->id, 'store_id' => $store->id, 'goods_receipt_id' => $goodsReceiptId, 'number' => $this->numbers->next('return', $store->organization_id, $by), 'reason' => $reason, 'created_by' => $by->id]);
            foreach ($lines as $line) {
                $item = InventoryItem::query()->where('organization_id', $store->organization_id)->findOrFail($line['item_id']);
                $batchId = (int) ($line['batch_id'] ?? 0);
                if (bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Quantities must be above zero.']);
                }
                $cost = $batchId > 0 ? (string) ($item->batches()->find($batchId)->unit_cost ?? 0) : (string) $item->standard_cost;
                $return->lines()->create(['item_id' => $item->id, 'batch_id' => $batchId, 'quantity' => $line['quantity'], 'unit_cost' => $cost]);
            }

            return $return->refresh();
        });
    }

    public function dispatch(ProcurementSupplierReturn $return, User $by): ProcurementSupplierReturn
    {
        return DB::transaction(function () use ($return, $by) {
            $return = ProcurementSupplierReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['status' => "A {$return->status} return cannot be dispatched."]);
            }
            $store = InventoryStore::findOrFail($return->store_id);
            foreach ($return->lines()->get() as $line) {
                $item = InventoryItem::findOrFail($line->item_id);
                $this->stock->take($store, $item, (int) $line->batch_id, (string) $line->quantity, 'return', "SRT:{$return->id}:{$line->id}", (string) $line->unit_cost, ProcurementSupplierReturn::class, $return->id, $by, $return->reason);
            }
            $return->update(['status' => 'dispatched']);

            return $return->refresh();
        });
    }

    public function credit(ProcurementSupplierReturn $return, string $creditNoteNumber, string $amount): ProcurementSupplierReturn
    {
        if ($return->status !== 'dispatched') {
            throw ValidationException::withMessages(['status' => 'Only a dispatched return can be credited.']);
        }
        if (bccomp($amount, '0', 2) < 0) {
            throw ValidationException::withMessages(['credit_amount' => 'The credit cannot be negative.']);
        }
        $return->update(['status' => 'credited', 'credit_note_number' => $creditNoteNumber, 'credit_amount' => $amount]);

        return $return->refresh();
    }
}
