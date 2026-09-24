<?php

namespace App\Events\Ipd;

use App\Models\Ipd\IpdAdmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientAdmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public IpdAdmission $admission) {}
}
