<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single place that enforces the PharmacyOrder status machine — no other service writes
 * PharmacyOrder::status directly. Mirrors LabOrderLifecycleService's "no arbitrary transitions" rule.
 */
class PharmacyOrderLifecycleService
{
    private const TRANSITIONS = [
        'pending' => ['under_review', 'partially_dispensed', 'fully_dispensed', 'cancelled', 'rejected'],
        'under_review' => ['pending', 'partially_dispensed', 'fully_dispensed', 'cancelled', 'rejected'],
        'partially_dispensed' => ['under_review', 'partially_dispensed', 'fully_dispensed', 'cancelled'],
        'fully_dispensed' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    public function transitionTo(PharmacyOrder $order, string $status): PharmacyOrder
    {
        if ($status === $order->status) {
            return $order;
        }

        if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move a pharmacy order from '{$order->status}' to '{$status}'.",
            ]);
        }

        $order->update(['status' => $status]);

        return $order->refresh();
    }

    public function cancel(PharmacyOrder $order, string $reason, User $user): PharmacyOrder
    {
        return DB::transaction(function () use ($order, $reason, $user) {
            $order = $this->transitionTo($order, 'cancelled');

            $order->update([
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $order->items()->whereNotIn('status', ['dispensed', 'cancelled'])->update(['status' => 'cancelled']);

            return $order->refresh();
        });
    }

    /**
     * Recomputes order status from its items' dispensing state — called after every dispensing.
     * Never marks fully_dispensed until every matched item has zero remaining quantity.
     */
    public function refreshFromItems(PharmacyOrder $order): PharmacyOrder
    {
        $order->loadMissing('items');
        $items = $order->items;

        if ($items->isEmpty() || in_array($order->status, ['cancelled', 'rejected'], true)) {
            return $order;
        }

        $dispensableItems = $items->where('status', '!=', 'cancelled');

        $allDispensed = $dispensableItems->isNotEmpty() && $dispensableItems->every(
            fn ($item) => $item->status === 'dispensed' || ($item->remainingQuantity() !== null && $item->remainingQuantity() === 0)
        );

        $anyDispensed = $items->contains(fn ($item) => $item->quantity_dispensed > 0);

        $targetStatus = match (true) {
            $allDispensed => 'fully_dispensed',
            $anyDispensed => 'partially_dispensed',
            default => $order->status,
        };

        return $this->transitionTo($order, $targetStatus);
    }
}
