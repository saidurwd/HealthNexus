<?php

namespace App\Listeners\Facility;

use App\Events\Ipd\BedReleased;
use App\Services\Facility\HousekeepingService;
use Throwable;

/** IPD says a bed was released: Facility makes the cleaning task. A failure never reaches the ward. */
class CreateCleaningTask
{
    public function __construct(private readonly HousekeepingService $housekeeping) {}

    public function handle(BedReleased $event): void
    {
        try {
            $this->housekeeping->onBedReleased($event->allocation);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
