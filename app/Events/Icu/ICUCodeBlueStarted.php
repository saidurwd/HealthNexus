<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuCriticalEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUCodeBlueStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuCriticalEvent $event) {}
}
