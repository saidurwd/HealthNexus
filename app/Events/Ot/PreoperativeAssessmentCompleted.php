<?php

namespace App\Events\Ot;

use App\Models\Ot\OtPreoperativeAssessment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PreoperativeAssessmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtPreoperativeAssessment $assessment) {}
}
