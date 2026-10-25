<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuHandoverRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUHandoverAcknowledged
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuHandoverRecord $handover) {}
}
