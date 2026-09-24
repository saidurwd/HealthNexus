<?php

namespace App\Events\Nursing;

use App\Models\Nursing\NursingDischargeChecklist;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NursingDischargeChecklistCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public NursingDischargeChecklist $checklist) {}
}
