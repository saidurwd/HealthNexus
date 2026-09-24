<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingAssessment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingAssessmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingAssessment $assessment) {}
}
