<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingCashierSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashierSessionClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingCashierSession $session) {}
}
