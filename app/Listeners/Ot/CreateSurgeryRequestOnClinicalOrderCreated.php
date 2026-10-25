<?php

namespace App\Listeners\Ot;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Services\Ot\OtSurgeryRequestService;

/**
 * Mirrors CreateLabOrderOnClinicalOrderCreated/CreateRadiologyOrderOnClinicalOrderCreated exactly
 * — a trivial order_type guard followed by the domain registration call (decision #1).
 */
class CreateSurgeryRequestOnClinicalOrderCreated
{
    public function __construct(private readonly OtSurgeryRequestService $requests) {}

    public function handle(ClinicalOrderCreated $event): void
    {
        if ($event->order->order_type !== 'surgery') {
            return;
        }

        $user = $event->order->orderedBy;

        if (! $user) {
            return;
        }

        $this->requests->createFromClinicalOrder($event->encounter, $event->order, $user);
    }
}
