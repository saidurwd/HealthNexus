<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuAlertEscalation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUCriticalAlertEscalated
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuAlertEscalation $escalation) {}
}
