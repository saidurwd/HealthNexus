<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuDischargeSummary;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUDischargeStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuDischargeSummary $summary) {}
}
