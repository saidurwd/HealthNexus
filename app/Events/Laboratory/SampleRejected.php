<?php

namespace App\Events\Laboratory;

use App\Models\Laboratory\LabSpecimen;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SampleRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabSpecimen $specimen) {}
}
