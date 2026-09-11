<?php

namespace App\Events;

use App\Models\Patient;

class PatientRegistered
{
    public function __construct(public Patient $patient) {}
}
