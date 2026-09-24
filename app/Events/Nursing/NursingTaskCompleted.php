<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingTask;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingTaskCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingTask $task) {}
}
