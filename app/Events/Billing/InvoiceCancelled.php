<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingInvoice $invoice) {}
}
