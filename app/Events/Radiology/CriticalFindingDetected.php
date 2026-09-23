<?php

namespace App\Events\Radiology;

use App\Models\Radiology\RadiologyCriticalFinding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriticalFindingDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public RadiologyCriticalFinding $finding) {}
}
