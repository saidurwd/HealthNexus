<?php

namespace App\Jobs\Ipd;

use App\Models\Ipd\IpdBedReservation;
use App\Services\Ipd\IpdBedReservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Finds bed reservations whose expiry has passed and are still unconverted, expires them, and
 * releases the bed back to Available — idempotent: skips reservations no longer 'reserved',
 * retry-safe since expire() is a no-op on an already-expired reservation.
 */
class ExpireBedReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IpdBedReservationService $reservations): void
    {
        IpdBedReservation::query()
            ->where('status', IpdBedReservation::STATUS_RESERVED)
            ->where('expires_at', '<', now())
            ->each(fn (IpdBedReservation $reservation) => $reservations->expire($reservation));
    }
}
