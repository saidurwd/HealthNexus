<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingCharge;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChargeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingCharge $charge) {}
}
