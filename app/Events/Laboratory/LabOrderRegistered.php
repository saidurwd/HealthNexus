<?php

namespace App\Events\Laboratory;

use App\Models\Laboratory\LabOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabOrderRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public LabOrder $order) {}
}
