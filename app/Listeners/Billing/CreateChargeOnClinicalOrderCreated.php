<?php

namespace App\Listeners\Billing;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Services\Billing\ChargeService;

class CreateChargeOnClinicalOrderCreated
{
    public function __construct(private readonly ChargeService $charges) {}

    public function handle(ClinicalOrderCreated $event): void
    {
        $encounter = $event->encounter;
        $order = $event->order;

        $this->charges->createFromClinicalEvent($order, 'clinical_order', $order->order_type, [
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'patient_id' => $order->patient_id,
            'encounter_id' => $encounter->id,
            'department_id' => $encounter->department_id,
            'provider_id' => $order->provider_id,
        ]);
    }
}
