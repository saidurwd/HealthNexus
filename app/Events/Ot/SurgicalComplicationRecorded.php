<?php

namespace App\Events\Ot;

use App\Models\Ot\OtSurgicalComplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurgicalComplicationRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtSurgicalComplication $complication) {}
}
