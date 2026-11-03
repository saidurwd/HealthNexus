<?php

namespace App\Events\Ed;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Base for ED domain events: ids only (guideline 7.3). */
abstract class EdVisitEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly int $visitId, public readonly int $organizationId, public readonly int $patientId) {}
}
