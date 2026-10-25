<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuCriticalEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUCriticalEventCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuCriticalEvent $event) {}
}
