<?php

namespace App\Events\Laboratory;

use App\Models\Laboratory\LabResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriticalResultDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabResult $result) {}
}
