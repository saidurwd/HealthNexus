<?php

namespace App\Listeners\Billing;

use App\Events\Clinical\EncounterCompleted;
use App\Services\Billing\ChargeService;

class CreateChargeOnEncounterCompleted
{
    public function __construct(private readonly ChargeService $charges) {}

    public function handle(EncounterCompleted $event): void
    {
        $encounter = $event->encounter;

        $this->charges->createFromClinicalEvent($encounter, 'encounter', 'completed', [
            'company_id' => $encounter->company_id,
            'branch_id' => $encounter->branch_id,
            'patient_id' => $encounter->patient_id,
            'encounter_id' => $encounter->id,
            'department_id' => $encounter->department_id,
            'provider_id' => $encounter->provider_id,
        ]);
    }
}
