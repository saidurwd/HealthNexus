<?php

namespace App\Events\Ot;

use App\Models\Ot\OtSpecimen;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpecimenCollected
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtSpecimen $specimen) {}
}
