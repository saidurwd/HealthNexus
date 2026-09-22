<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingPayment $payment) {}
}
