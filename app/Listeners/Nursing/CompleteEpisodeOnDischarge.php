<?php

namespace App\Listeners\Nursing;

use App\Events\Ipd\PatientDischarged;
use App\Services\Nursing\NursingEpisodeService;

class CompleteEpisodeOnDischarge
{
    public function __construct(private readonly NursingEpisodeService $episodes) {}

    public function handle(PatientDischarged $event): void
    {
        $this->episodes->complete($event->admission);
    }
}
