<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NurseAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingAssignment $assignment) {}
}
