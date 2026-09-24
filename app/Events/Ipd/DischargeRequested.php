<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdDischargeRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DischargeRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdDischargeRequest $request) {}
}
