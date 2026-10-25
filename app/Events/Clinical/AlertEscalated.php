<?php

namespace App\Events\Clinical;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Carries ids only (guideline 7.3). */
class AlertEscalated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $alertId,
        public readonly int $organizationId,
        public readonly string $category,
        public readonly string $severity,
    ) {}
}
