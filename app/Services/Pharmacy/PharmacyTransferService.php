<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Models\Pharmacy\PharmacyTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * request() -> approve() -> dispatch() (locked deduction from the source store) -> receive()
 * (credit the destination store against the SAME batch_id — batches are not store-scoped).
 */
class PharmacyTransferService
{
    public function __construct(
        private readonly PharmacyNumberGenerator $numbers,
        private readonly PharmacyStockService $stock,
    ) {}

    /**
     * @param  array<int, array{medication_id:int, batch_id:int, quantity_requested:int}>  $items
     */
    public function request(PharmacyStore $source, PharmacyStore $destination, array $items, User $user): PharmacyTransfer
    {
        if ($source->id === $destination->id) {
            throw ValidationException::withMessages(['destination_store_id' => 'Source and destination stores must differ.']);
        }

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'At least one item must be requested for transfer.']);
        }

        return DB::transaction(function () use ($source, $destination, $items, $user) {
            $transfer = PharmacyTransfer::create([
                'company_id' => $source->company_id,
                'branch_id' => $source->branch_id,
                'source_store_id' => $source->id,
                'destination_store_id' => $destination->id,
                'transfer_number' => $this->numbers->generateTransferNumber($source->company_id, $source->branch_id),
                'status' => 'requested',
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'medication_id' => $item['medication_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function approve(PharmacyTransfer $transfer, User $user): PharmacyTransfer
    {
        $this->assertStatus($transfer, 'requested');

        $transfer->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);

        return $transfer->refresh();
    }

    public function dispatch(PharmacyTransfer $transfer, User $user): PharmacyTransfer
    {
        $this->assertStatus($transfer, 'approved');

        return DB::transaction(function () use ($transfer, $user) {
            $transfer->loadMissing('sourceStore', 'items.medication', 'items.batch');

            foreach ($transfer->items as $item) {
                $this->stock->debit(
                    $transfer->sourceStore,
                    $item->medication,
                    $item->batch,
                    $item->quantity_requested,
                    PharmacyStockTransaction::TYPE_TRANSFER_OUT,
                    $user,
                    null,
                    $transfer,
                );

                $item->update(['quantity_dispatched' => $item->quantity_requested]);
            }

            $transfer->update(['status' => 'dispatched', 'dispatched_by' => $user->id, 'dispatched_at' => now()]);

            return $transfer->refresh()->load('items');
        });
    }

    public function receive(PharmacyTransfer $transfer, User $user): PharmacyTransfer
    {
        $this->assertStatus($transfer, 'dispatched');

        return DB::transaction(function () use ($transfer, $user) {
            $transfer->loadMissing('destinationStore', 'items.medication', 'items.batch');

            foreach ($transfer->items as $item) {
                $this->stock->receiveIntoBatch(
                    $transfer->destinationStore,
                    $item->batch,
                    $item->quantity_dispatched,
                    $user,
                    PharmacyStockTransaction::TYPE_TRANSFER_IN,
                    $transfer,
                );

                $item->update(['quantity_received' => $item->quantity_dispatched]);
            }

            $transfer->update(['status' => 'received', 'received_by' => $user->id, 'received_at' => now()]);

            return $transfer->refresh()->load('items');
        });
    }

    private function assertStatus(PharmacyTransfer $transfer, string $expected): void
    {
        if ($transfer->status !== $expected) {
            throw ValidationException::withMessages(['status' => "This transfer must be '{$expected}' for this action (currently '{$transfer->status}')."]);
        }
    }
}
