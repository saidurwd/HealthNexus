<?php

namespace App\Events\Ot;

use App\Models\Ot\OtRecoveryRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecoveryCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public OtRecoveryRecord $recovery) {}
}
