<?php

namespace App\Services\Billing;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Events\Billing\PaymentCompleted;
use App\Models\Billing\BillingCashierSession;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every collect() call creates a brand-new BillingPayment row — an existing payment is never
 * mutated to represent a second transaction. Supports any number of partial payments against
 * one invoice.
 */
class PaymentService
{
    public function __construct(
        private readonly BillingNumberGenerator $numbers,
        private readonly ReceiptService $receipts,
        private readonly RevenuePostingInterface $revenuePoster,
    ) {}

    /**
     * @param  array{payment_method_id:int,amount:string,transaction_reference?:string|null,payment_date?:string,currency?:string,corporate_id?:int|null,notes?:string|null}  $paymentData
     */
    public function collect(BillingInvoice $invoice, array $paymentData, User $cashier, ?BillingCashierSession $session = null): BillingPayment
    {
        return DB::transaction(function () use ($invoice, $paymentData, $cashier, $session) {
            if (! in_array($invoice->status, ['finalized', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['invoice' => "Cannot collect payment against an invoice in '{$invoice->status}' status."]);
            }

            $amount = (string) $paymentData['amount'];

            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            if (bccomp($amount, $invoice->remainingDue(), 2) === 1) {
                throw ValidationException::withMessages(['amount' => 'Payment amount cannot exceed the invoice due amount.']);
            }

            $payment = BillingPayment::create([
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'patient_id' => $invoice->patient_id,
                'invoice_id' => $invoice->id,
                'corporate_id' => $paymentData['corporate_id'] ?? $invoice->corporate_id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'cashier_session_id' => $session?->id,
                'payment_number' => $this->numbers->generatePaymentNumber($invoice->company_id, $invoice->branch_id),
                'currency' => $paymentData['currency'] ?? $invoice->currency,
                'amount' => $amount,
                'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                'status' => 'completed',
                'completed_at' => now(),
                'received_by' => $cashier->id,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            $newPaid = bcadd((string) $invoice->paid_amount, $amount, 2);
            $newDue = bcsub((string) $invoice->grand_total, $newPaid, 2);

            $invoice->update([
                'paid_amount' => $newPaid,
                'due_amount' => $newDue,
                'status' => bccomp($newDue, '0', 2) <= 0 ? 'paid' : 'partially_paid',
            ]);

            $this->receipts->generate($payment, $cashier);

            event(new PaymentCompleted($payment));
            $this->revenuePoster->postPayment($payment);

            return $payment->refresh();
        });
    }

    public function cancel(BillingPayment $payment, string $reason, User $user): BillingPayment
    {
        return DB::transaction(function () use ($payment, $reason, $user) {
            if (! $payment->isMutable()) {
                throw ValidationException::withMessages(['payment' => 'A completed payment cannot be cancelled — use a refund instead.']);
            }

            $payment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'notes' => trim(($payment->notes ? $payment->notes."\n" : '')."Cancelled: {$reason}"),
            ]);

            return $payment->refresh();
        });
    }
}
