<?php

namespace App\Listeners\Finance;

use App\Events\Billing\AdjustmentApproved;
use App\Events\Billing\InvoiceCancelled;
use App\Events\Billing\InvoiceFinalized;
use App\Events\Billing\PaymentCompleted;
use App\Events\Billing\RefundProcessed;
use App\Services\Finance\PostingEngine;

/** Billing's money events become journals as they happen. A failure never reaches the cashier: it waits in Finance > Posting problems. */
class PostToLedger
{
    public function __construct(private readonly PostingEngine $engine) {}

    public function invoiceFinalized(InvoiceFinalized $e): void
    {
        $this->engine->post('invoice.finalized', $e->invoice);
    }

    public function invoiceCancelled(InvoiceCancelled $e): void
    {
        $this->engine->post('invoice.cancelled', $e->invoice);
    }

    public function paymentCompleted(PaymentCompleted $e): void
    {
        $this->engine->post('payment.completed', $e->payment);
    }

    public function refundProcessed(RefundProcessed $e): void
    {
        $this->engine->post('refund.processed', $e->refund);
    }

    public function adjustmentApproved(AdjustmentApproved $e): void
    {
        $this->engine->post('adjustment.approved', $e->adjustment);
    }
}
