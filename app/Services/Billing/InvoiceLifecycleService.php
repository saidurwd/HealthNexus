<?php

namespace App\Services\Billing;

use App\Events\Billing\InvoiceCancelled;
use App\Events\Billing\InvoiceFinalized;
use App\Models\Billing\BillingInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Controls invoice status transitions. This is the single place that assigns invoice numbers
 * and enforces immutability after finalization — InvoiceService::recalculate()/addCharges()
 * both refuse to run once an invoice is no longer mutable.
 */
class InvoiceLifecycleService
{
    public function __construct(private readonly BillingNumberGenerator $numbers) {}

    public function finalize(BillingInvoice $invoice, User $user): BillingInvoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            if (! in_array($invoice->status, ['draft', 'pending'], true)) {
                throw ValidationException::withMessages(['invoice' => "Cannot finalize an invoice in '{$invoice->status}' status."]);
            }

            if (bccomp((string) $invoice->grand_total, '0', 2) < 0) {
                throw ValidationException::withMessages(['invoice' => 'Invoice total cannot be negative.']);
            }

            if ($invoice->items()->doesntExist()) {
                throw ValidationException::withMessages(['invoice' => 'Cannot finalize an invoice with no line items.']);
            }

            $invoice->update([
                'invoice_number' => $this->numbers->generateInvoiceNumber($invoice->company_id, $invoice->branch_id),
                'status' => $this->deriveStatusAfterFinalize($invoice),
                'finalized_by' => $user->id,
                'finalized_at' => now(),
            ]);

            $invoice = $invoice->refresh();

            event(new InvoiceFinalized($invoice));

            return $invoice;
        });
    }

    public function cancel(BillingInvoice $invoice, string $reason, User $user): BillingInvoice
    {
        return DB::transaction(function () use ($invoice, $reason, $user) {
            if (! $invoice->canTransitionTo('cancelled')) {
                throw ValidationException::withMessages(['invoice' => "Cannot cancel an invoice in '{$invoice->status}' status."]);
            }

            $invoice->update([
                'status' => 'cancelled',
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $invoice = $invoice->refresh();

            event(new InvoiceCancelled($invoice));

            return $invoice;
        });
    }

    public function writeOff(BillingInvoice $invoice, string $reason, User $user): BillingInvoice
    {
        return DB::transaction(function () use ($invoice, $reason, $user) {
            if (! $invoice->canTransitionTo('written_off')) {
                throw ValidationException::withMessages(['invoice' => "Cannot write off an invoice in '{$invoice->status}' status."]);
            }

            $invoice->update([
                'status' => 'written_off',
                'written_off_by' => $user->id,
                'written_off_at' => now(),
                'notes' => trim(($invoice->notes ? $invoice->notes."\n" : '')."Written off: {$reason}"),
            ]);

            return $invoice->refresh();
        });
    }

    private function deriveStatusAfterFinalize(BillingInvoice $invoice): string
    {
        if (bccomp((string) $invoice->paid_amount, '0', 2) <= 0) {
            return 'finalized';
        }

        return bccomp((string) $invoice->paid_amount, (string) $invoice->grand_total, 2) >= 0 ? 'paid' : 'partially_paid';
    }
}
