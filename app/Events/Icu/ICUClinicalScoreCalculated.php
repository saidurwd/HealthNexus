<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuClinicalScoreResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUClinicalScoreCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuClinicalScoreResult $result) {}
}
