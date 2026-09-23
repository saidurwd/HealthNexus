<?php

namespace App\Listeners\Pharmacy;

use App\Events\Clinical\PrescriptionIssued;
use App\Services\Pharmacy\PharmacyOrderService;

class CreatePharmacyOrderOnPrescriptionIssued
{
    public function __construct(private readonly PharmacyOrderService $orders) {}

    public function handle(PrescriptionIssued $event): void
    {
        $this->orders->registerFromPrescription($event->prescription);
    }
}
