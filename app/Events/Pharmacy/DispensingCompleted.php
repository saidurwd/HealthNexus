<?php

namespace App\Events\Pharmacy;

use App\Models\Pharmacy\PharmacyDispensing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispensingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public PharmacyDispensing $dispensing) {}
}
