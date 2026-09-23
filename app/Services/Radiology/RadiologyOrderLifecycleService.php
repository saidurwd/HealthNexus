<?php

namespace App\Services\Radiology;

use App\Models\Radiology\RadiologyOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single place that enforces the RadiologyOrder status machine — no other service writes
 * RadiologyOrder::status directly. Spec §19: "Do not allow arbitrary status changes."
 */
class RadiologyOrderLifecycleService
{
    private const TRANSITIONS = [
        'ordered' => ['registered', 'cancelled'],
        'registered' => ['scheduled', 'cancelled'],
        'scheduled' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['preparing', 'ready', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => ['images_available', 'reporting', 'cancelled'],
        'images_available' => ['reporting', 'cancelled'],
        'reporting' => ['reported', 'cancelled'],
        'reported' => [],
        'cancelled' => [],
        'no_show' => [],
        'rejected' => [],
    ];

    public function transitionTo(RadiologyOrder $order, string $status): RadiologyOrder
    {
        if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move a radiology order from '{$order->status}' to '{$status}'.",
            ]);
        }

        $order->update(['status' => $status]);

        return $order->refresh();
    }

    public function cancel(RadiologyOrder $order, string $reason, User $user): RadiologyOrder
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
