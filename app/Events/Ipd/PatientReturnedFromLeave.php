<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdPatientLeave;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientReturnedFromLeave
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdPatientLeave $leave) {}
}
