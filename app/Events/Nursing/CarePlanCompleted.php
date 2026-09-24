<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingCarePlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarePlanCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingCarePlan $carePlan) {}
}
