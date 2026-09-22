<?php

namespace App\Services\Billing;

use App\Events\Billing\ReceiptGenerated;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public function __construct(private readonly BillingNumberGenerator $numbers) {}

    public function generate(BillingPayment $payment, User $issuedBy): BillingReceipt
    {
        return DB::transaction(function () use ($payment, $issuedBy) {
            $existing = BillingReceipt::query()->where('payment_id', $payment->id)->first();

            if ($existing) {
                return $existing;
            }

            $receipt = BillingReceipt::create([
                'company_id' => $payment->company_id,
                'branch_id' => $payment->branch_id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'receipt_number' => $this->numbers->generateReceiptNumber($payment->company_id, $payment->branch_id),
                'status' => 'issued',
                'issued_at' => now(),
                'issued_by' => $issuedBy->id,
            ]);

            event(new ReceiptGenerated($receipt));

            return $receipt;
        });
    }

    /**
     * Voids a receipt as part of a refund/cancellation workflow — never a bare delete.
     */
    public function void(BillingReceipt $receipt, string $reason, User $user): BillingReceipt
    {
        if ($receipt->status === 'voided') {
            return $receipt;
        }

        $receipt->update(['status' => 'voided']);

        return $receipt->refresh();
    }
}
