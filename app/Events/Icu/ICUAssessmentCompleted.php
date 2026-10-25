<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuAssessment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUAssessmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuAssessment $assessment) {}
}
