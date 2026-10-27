<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\InventoryTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Store to store (S08). Two ledger rows, with an in-transit state between them: dispatching takes the stock out of the sender, receiving
 * puts it into the receiver. Whatever the receiver did not get goes back to the sender, so nothing is ever lost between the two.
 */
class InventoryTransferService
{
    public function __construct(private readonly InventoryNumbers $numbers, private readonly InventoryStockService $stock) {}

    /** @param  list<array{item_id: int, quantity: float|int|string, batch_id?: int}>  $lines  base units; without a batch the oldest-expiry stock is chosen at dispatch */
    public function request(User $by, InventoryStore $from, InventoryStore $to, array $lines, ?string $notes = null): InventoryTransfer
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to_store' => 'Choose a different store to send to.']);
        }
        if ($from->organization_id !== $to->organization_id) {
            throw ValidationException::withMessages(['to_store' => 'Stores belong to different organizations.']);
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($by, $from, $to, $lines, $notes) {
            $transfer = InventoryTransfer::create(['organization_id' => $from->organization_id, 'from_store_id' => $from->id, 'to_store_id' => $to->id, 'number' => $this->numbers->next('transfer', $from->organization_id, $by), 'notes' => $notes, 'requested_by' => $by->id]);
            foreach ($lines as $line) {
                InventoryItem::query()->where('organization_id', $from->organization_id)->findOrFail($line['item_id']);
                if (bccomp((string) $line['quantity'], '0', 4) <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Quantities must be above zero.']);
                }
                $transfer->lines()->create(['item_id' => $line['item_id'], 'batch_id' => $line['batch_id'] ?? 0, 'quantity' => $line['quantity']]);
            }

            return $transfer->refresh();
        });
    }

    public function dispatch(InventoryTransfer $transfer, User $by): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $by) {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status !== 'requested') {
                throw ValidationException::withMessages(['status' => "A {$transfer->status} transfer cannot be dispatched."]);
            }
            $from = $transfer->fromStore;
            foreach ($transfer->lines()->get() as $line) {
                $item = InventoryItem::findOrFail($line->item_id);
                $picks = (int) $line->batch_id > 0
                    ? [['batch_id' => (int) $line->batch_id, 'quantity' => (string) $line->quantity, 'unit_cost' => (string) ($item->batches()->find($line->batch_id)->unit_cost ?? $item->standard_cost)]]
                    : $this->stock->allocate($from, $item, (string) $line->quantity);
                $first = true;
                foreach ($picks as $pick) {
                    $this->stock->take($from, $item, $pick['batch_id'], $pick['quantity'], 'transfer_out', "TRF:{$transfer->id}:{$line->id}:{$pick['batch_id']}:out", $pick['unit_cost'], InventoryTransfer::class, $transfer->id, $by);
                    if ($first) {
                        $line->update(['batch_id' => $pick['batch_id'], 'quantity' => $pick['quantity'], 'unit_cost' => $pick['unit_cost']]);
                        $first = false;
                    } else {
                        $transfer->lines()->create(['item_id' => $item->id, 'batch_id' => $pick['batch_id'], 'quantity' => $pick['quantity'], 'unit_cost' => $pick['unit_cost']]);
                    }
                }
            }
            $transfer->update(['status' => 'dispatched', 'dispatched_by' => $by->id, 'dispatched_at' => now()]);

            return $transfer->refresh();
        });
    }

    /** @param  array<int, float|int|string>  $received  transfer line id => quantity actually received; missing = all of it */
    public function receive(InventoryTransfer $transfer, User $by, array $received = []): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $by, $received) {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status !== 'dispatched') {
                throw ValidationException::withMessages(['status' => "A {$transfer->status} transfer cannot be received."]);
            }
            $to = $transfer->toStore;
            $from = $transfer->fromStore;
            foreach ($transfer->lines()->get() as $line) {
                $item = InventoryItem::findOrFail($line->item_id);
                $got = (string) ($received[$line->id] ?? $line->quantity);
                if (bccomp($got, '0', 4) < 0 || bccomp($got, (string) $line->quantity, 4) > 0) {
                    throw ValidationException::withMessages(['quantity' => "{$item->name}: received must be between 0 and {$line->quantity}."]);
                }
                $short = bcsub((string) $line->quantity, $got, 4);
                if (bccomp($got, '0', 4) > 0) {
                    $this->stock->receive($to, $item, (int) $line->batch_id, $got, 'transfer_in', "TRF:{$transfer->id}:{$line->id}:in", (string) $line->unit_cost, InventoryTransfer::class, $transfer->id, $by);
                }
                if (bccomp($short, '0', 4) > 0) {
                    $this->stock->receive($from, $item, (int) $line->batch_id, $short, 'transfer_in', "TRF:{$transfer->id}:{$line->id}:back", (string) $line->unit_cost, InventoryTransfer::class, $transfer->id, $by, 'Not received: returned to the sending store');
                }
                $line->update(['received_quantity' => $got]);
            }
            $transfer->update(['status' => 'received', 'received_by' => $by->id, 'received_at' => now()]);

            return $transfer->refresh();
        });
    }

    /** A request can be withdrawn; stock already dispatched is received back at the sender. */
    public function cancel(InventoryTransfer $transfer, User $by): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $by) {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($transfer->status === 'dispatched') {
                foreach ($transfer->lines()->get() as $line) {
                    $this->stock->receive($transfer->fromStore, InventoryItem::findOrFail($line->item_id), (int) $line->batch_id, (string) $line->quantity, 'transfer_in', "TRF:{$transfer->id}:{$line->id}:cancel", (string) $line->unit_cost, InventoryTransfer::class, $transfer->id, $by, 'Transfer cancelled in transit');
                }
            } elseif ($transfer->status !== 'requested') {
                throw ValidationException::withMessages(['status' => "A {$transfer->status} transfer cannot be cancelled."]);
            }
            $transfer->update(['status' => 'cancelled']);

            return $transfer->refresh();
        });
    }
}
