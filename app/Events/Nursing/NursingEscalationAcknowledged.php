<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingEscalation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingEscalationAcknowledged
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingEscalation $escalation) {}
}
