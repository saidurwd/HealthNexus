<?php

namespace App\Events\Pharmacy;

use App\Models\Pharmacy\PharmacyDispensingItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicationDispensed
{
    use Dispatchable, SerializesModels;

    public function __construct(public PharmacyDispensingItem $item) {}
}
