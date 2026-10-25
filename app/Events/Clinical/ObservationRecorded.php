<?php

namespace App\Events\Clinical;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Carries ids only (guideline 7.3): listeners re-read the observation under their own scope. */
class ObservationRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $observationId,
        public readonly int $patientId,
        public readonly string $code,
        public readonly string $contextType,
        public readonly ?int $contextId,
        public readonly int $organizationId,
    ) {}
}
