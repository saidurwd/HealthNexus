<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuInfusion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUInfusionTitrated
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuInfusion $infusion) {}
}
