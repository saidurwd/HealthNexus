<?php

namespace App\Events\Icu;

use App\Models\Icu\IcuAdmissionRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ICUAdmissionRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public IcuAdmissionRequest $request) {}
}
