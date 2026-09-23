<?php

namespace App\Events\Laboratory;

use App\Models\Laboratory\LabReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabReportFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabReport $report) {}
}
