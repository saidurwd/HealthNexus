<?php

namespace App\Contracts\Billing;

use App\Models\Billing\ChargeEventLog;
use App\Services\Billing\Events\ChargeEventData;

/**
 * The only door from a clinical/operational module to Billing (guideline 11.1): announce what was done; Billing prices it.
 * Publishing the same event twice never charges twice.
 */
interface ChargeEventPublisher
{
    public function publish(ChargeEventData $event): ChargeEventLog;

    /** Asks Billing to undo the charge for a previously published event. Original rows stay; see ChargeEventLog::outcome. */
    public function reverse(int $organizationId, string $idempotencyKey, string $reason, ?int $userId = null): ChargeEventLog;
}
