<?php

namespace App\Events\Radiology;

use App\Models\Radiology\RadiologyExamination;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RadiologyExamCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public RadiologyExamination $examination) {}
}
