<?php

namespace App\Services\Billing;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Events\Billing\RefundProcessed;
use App\Events\Billing\RefundRequested;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingRefund;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Requested -> Approved -> Processed. Never mutates the original BillingPayment's amount —
 * only its status, once the refunded total reaches the full payment amount.
 */
class RefundService
{
    public function __construct(
        private readonly BillingNumberGenerator $numbers,
        private readonly SettingsService $settings,
        private readonly RevenuePostingInterface $revenuePoster,
    ) {}

    public function request(BillingPayment $payment, string $amount, string $reason, User $user): BillingRefund
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $user) {
            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
            }

            $refundable = bcsub((string) $payment->amount, $this->refundedAmountFor($payment), 2);

            if (bccomp($amount, $refundable, 2) === 1) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the remaining refundable amount on this payment.']);
            }

            $refund = BillingRefund::create([
                'company_id' => $payment->company_id,
                'branch_id' => $payment->branch_id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'patient_id' => $payment->patient_id,
                'refund_number' => $this->numbers->generateRefundNumber($payment->company_id, $payment->branch_id),
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'requested',
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);

            event(new RefundRequested($refund));

            return $refund;
        });
    }

    public function approve(BillingRefund $refund, User $approver, ?string $note = null): BillingRefund
    {
        return DB::transaction(function () use ($refund, $approver, $note) {
            if (! $refund->canApprove()) {
                throw ValidationException::withMessages(['refund' => "Cannot approve a refund in '{$refund->status}' status."]);
            }

            if ((int) $refund->requested_by === $approver->id) {
                throw ValidationException::withMessages(['refund' => 'A refund cannot be approved by the same user who requested it.']);
            }

            $refund->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            return $refund->refresh();
        });
    }

    public function process(BillingRefund $refund, User $processor, ?string $processorReference = null): BillingRefund
    {
        return DB::transaction(function () use ($refund, $processor, $processorReference) {
            if ($refund->status !== 'approved') {
                throw ValidationException::withMessages(['refund' => "Cannot process a refund in '{$refund->status}' status."]);
            }

            $refund->update([
                'status' => 'processed',
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'processor_reference' => $processorReference,
            ]);

            $payment = $refund->payment;
            $refundedTotal = $this->refundedAmountFor($payment, includeThis: $refund);

            if (bccomp($refundedTotal, (string) $payment->amount, 2) >= 0) {
                $payment->update(['status' => 'refunded']);
            }

            if ($invoice = $refund->invoice) {
                $newPaid = bcsub((string) $invoice->paid_amount, (string) $refund->amount, 2);
                $newPaid = bccomp($newPaid, '0', 2) === -1 ? '0.00' : $newPaid;
                $newDue = bcsub((string) $invoice->grand_total, $newPaid, 2);

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'due_amount' => $newDue,
                    'status' => bccomp($newPaid, '0', 2) <= 0 ? 'refunded' : 'partially_paid',
                    'refunded_by' => bccomp($newPaid, '0', 2) <= 0 ? $processor->id : $invoice->refunded_by,
                    'refunded_at' => bccomp($newPaid, '0', 2) <= 0 ? now() : $invoice->refunded_at,
                ]);
            }

            $refund = $refund->refresh();

            event(new RefundProcessed($refund));
            $this->revenuePoster->postRefund($refund);

            return $refund;
        });
    }

    private function refundedAmountFor(BillingPayment $payment, ?BillingRefund $includeThis = null): string
    {
        $total = $payment->refunds()
            ->whereIn('status', ['requested', 'approved', 'processed'])
            ->when($includeThis, fn ($q) => $q->whereKeyNot($includeThis->id))
            ->pluck('amount')
            ->reduce(fn (string $carry, $amount) => bcadd($carry, (string) $amount, 2), '0.00');

        if ($includeThis) {
            $total = bcadd($total, (string) $includeThis->amount, 2);
        }

        return $total;
    }
}
