<?php

namespace App\Events\Ot;

use App\Models\Ot\OtSurgery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurgeryCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtSurgery $surgery) {}
}
