<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReceiptGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingReceipt $receipt) {}
}
