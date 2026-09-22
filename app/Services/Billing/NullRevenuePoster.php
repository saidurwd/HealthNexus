<?php

namespace App\Services\Billing;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;

/**
 * Default no-op RevenuePostingInterface binding — keeps the accounting seam injectable without
 * shipping general-ledger logic. Swap the binding in AppServiceProvider once a real integration
 * exists.
 */
class NullRevenuePoster implements RevenuePostingInterface
{
    public function postInvoiceRevenue(BillingInvoice $invoice): void {}

    public function postPayment(BillingPayment $payment): void {}

    public function postRefund(BillingRefund $refund): void {}

    public function postAdjustment(BillingAdjustment $adjustment): void {}
}
