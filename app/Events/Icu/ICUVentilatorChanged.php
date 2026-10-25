<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuVentilatorEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUVentilatorChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuVentilatorEvent $event) {}
}
