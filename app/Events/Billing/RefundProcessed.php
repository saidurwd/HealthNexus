<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingRefund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingRefund $refund) {}
}
