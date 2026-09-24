<?php

namespace App\Listeners\Nursing;

use App\Events\Ipd\PatientAdmitted;
use App\Services\Nursing\NursingEpisodeService;

class StartEpisodeOnAdmission
{
    public function __construct(private readonly NursingEpisodeService $episodes) {}

    public function handle(PatientAdmitted $event): void
    {
        $this->episodes->startFromAdmission($event->admission);
    }
}
