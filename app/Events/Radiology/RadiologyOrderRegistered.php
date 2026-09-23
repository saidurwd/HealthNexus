<?php

namespace App\Events\Radiology;

use App\Models\Radiology\RadiologyOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RadiologyOrderRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public RadiologyOrder $order) {}
}
