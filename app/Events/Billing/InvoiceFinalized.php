<?php

namespace App\Events\Billing;

use App\Models\Billing\BillingInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public BillingInvoice $invoice) {}
}
