<?php

namespace App\Listeners\Radiology;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Services\Radiology\RadiologyRegistrationService;

class CreateRadiologyOrderOnClinicalOrderCreated
{
    public function __construct(private readonly RadiologyRegistrationService $registration) {}

    public function handle(ClinicalOrderCreated $event): void
    {
        if ($event->order->order_type !== 'radiology') {
            return;
        }

        $this->registration->registerFromClinicalOrder($event->encounter, $event->order);
    }
}
