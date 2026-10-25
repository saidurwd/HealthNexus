<?php

namespace App\Events\Ot;

use App\Models\Ot\OtImplant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImplantRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtImplant $implant) {}
}
