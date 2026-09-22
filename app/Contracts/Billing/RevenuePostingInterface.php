<?php

namespace App\Contracts\Billing;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;

/**
 * Seam for a future accounting/ERP integration. Phase 4 ships no implementation beyond the
 * no-op default (see NullRevenuePoster) — this only exists so invoice/payment/refund/adjustment
 * services have somewhere to call into without hardcoding a vendor or building a general ledger.
 */
interface RevenuePostingInterface
{
    public function postInvoiceRevenue(BillingInvoice $invoice): void;

    public function postPayment(BillingPayment $payment): void;

    public function postRefund(BillingRefund $refund): void;

    public function postAdjustment(BillingAdjustment $adjustment): void;
}
