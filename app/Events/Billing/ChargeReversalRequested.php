<?php

namespace App\Events\Billing;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** A module asks Billing to undo the charge it published under this key. Ids/keys only. */
class ChargeReversalRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $organizationId,
        public readonly string $idempotencyKey,
        public readonly string $reason,
        public readonly ?int $requestedBy = null,
    ) {}
}
