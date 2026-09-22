<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingAdjustment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdjustmentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingAdjustment $adjustment) {}
}
