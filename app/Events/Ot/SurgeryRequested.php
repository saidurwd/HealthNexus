<?php

namespace App\Events\Ot;

use App\Models\Ot\OtSurgeryRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurgeryRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtSurgeryRequest $request) {}
}
