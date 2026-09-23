<?php

namespace App\Listeners\Laboratory;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Services\Laboratory\LabRegistrationService;

class CreateLabOrderOnClinicalOrderCreated
{
    public function __construct(private readonly LabRegistrationService $registration) {}

    public function handle(ClinicalOrderCreated $event): void
    {
        if ($event->order->order_type !== 'laboratory') {
            return;
        }

        $this->registration->registerFromClinicalOrder($event->encounter, $event->order);
    }
}
