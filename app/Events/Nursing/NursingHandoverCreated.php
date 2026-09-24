<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingHandover;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingHandoverCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingHandover $handover) {}
}
