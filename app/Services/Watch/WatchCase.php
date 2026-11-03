<?php

namespace App\Services\Watch;

/** One case in a window: a patient, and the district of their permanent address when it is recorded. */
final class WatchCase
{
    public function __construct(public readonly int $patient_id, public readonly ?int $district_id) {}
}
