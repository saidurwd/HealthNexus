<?php

namespace App\Events\Radiology;

use App\Models\Radiology\RadiologyReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RadiologyReportFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public RadiologyReport $report) {}
}
