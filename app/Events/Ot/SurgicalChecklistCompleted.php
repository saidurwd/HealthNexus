<?php

namespace App\Events\Ot;

use App\Models\Ot\OtSurgicalSafetyChecklist;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurgicalChecklistCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtSurgicalSafetyChecklist $checklist) {}
}
