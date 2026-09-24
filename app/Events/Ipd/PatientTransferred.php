<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdBedMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdBedMovement $movement) {}
}
