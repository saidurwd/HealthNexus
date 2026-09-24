<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdAdmissionRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdmissionApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdAdmissionRequest $request) {}
}
