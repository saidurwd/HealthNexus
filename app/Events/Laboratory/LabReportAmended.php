<?php

namespace App\Events\Laboratory;

use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabReportAmended
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabReport $report, public LabResult $amendedResult) {}
}
