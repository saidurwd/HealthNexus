<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single place that enforces the LabOrder status machine — no other service writes
 * LabOrder::status directly. Spec §15: "Do not allow arbitrary status changes."
 */
class LabOrderLifecycleService
{
    private const TRANSITIONS = [
        'ordered' => ['registered', 'cancelled'],
        'registered' => ['awaiting_collection', 'cancelled'],
        'awaiting_collection' => ['collected', 'cancelled'],
        'collected' => ['received', 'awaiting_collection', 'cancelled'],
        'received' => ['processing', 'awaiting_collection', 'cancelled'],
        'processing' => ['partial_result', 'awaiting_validation', 'cancelled'],
        'partial_result' => ['processing', 'awaiting_validation', 'cancelled'],
        'awaiting_validation' => ['validated', 'processing', 'cancelled'],
        'validated' => ['reported', 'cancelled'],
        'reported' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    public function transitionTo(LabOrder $order, string $status): LabOrder
    {
        if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move a lab order from '{$order->status}' to '{$status}'.",
            ]);
        }

        $order->update(['status' => $status]);

        return $order->refresh();
    }

    public function cancel(LabOrder $order, string $reason, User $user): LabOrder
    {
        return DB::transaction(function () use ($order, $reason, $user) {
            $order = $this->transitionTo($order, 'cancelled');

            $order->update([
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $order->items()->whereNotIn('status', ['cancelled'])->update(['status' => 'cancelled']);

            return $order->refresh();
        });
    }
}
