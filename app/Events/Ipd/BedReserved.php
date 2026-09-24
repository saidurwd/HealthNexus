<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdBedReservation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BedReserved
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdBedReservation $reservation) {}
}
