<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuCriticalAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUCriticalAlertTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuCriticalAlert $alert) {}
}
