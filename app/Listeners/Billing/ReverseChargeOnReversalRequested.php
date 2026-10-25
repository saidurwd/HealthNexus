<?php

namespace App\Listeners\Billing;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Events\Billing\ChargeReversalRequested;

class ReverseChargeOnReversalRequested
{
    public function __construct(private readonly ChargeEventPublisher $publisher) {}

    public function handle(ChargeReversalRequested $event): void
    {
        $this->publisher->reverse($event->organizationId, $event->idempotencyKey, $event->reason, $event->requestedBy);
    }
}
