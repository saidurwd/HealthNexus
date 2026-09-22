<?php

namespace App\Services\Billing;

use App\Contracts\Billing\RevenuePostingInterface;
use App\Events\Billing\AdjustmentApproved;
use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Controlled discount/price/credit/debit/write-off adjustments to a finalized invoice. Every
 * adjustment is requested with a reason, then only takes effect on the invoice once approved —
 * a cashier can never silently reduce a bill.
 */
class AdjustmentService
{
    private const TYPES = ['discount', 'price', 'credit', 'debit', 'write_off'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly RevenuePostingInterface $revenuePoster,
    ) {}

    public function request(BillingInvoice $invoice, string $type, string $originalValue, string $newValue, string $reason, User $user): BillingAdjustment
    {
        if (! in_array($type, self::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Invalid adjustment type.']);
        }

        return DB::transaction(function () use ($invoice, $type, $originalValue, $newValue, $reason, $user) {
            return BillingAdjustment::create([
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'payment_id' => null,
                'adjustable_type' => BillingInvoice::class,
                'adjustable_id' => $invoice->id,
                'type' => $type,
                'currency' => $invoice->currency,
                'original_value' => $originalValue,
                'new_value' => $newValue,
                'difference' => bcsub($newValue, $originalValue, 2),
                'reason' => $reason,
                'status' => 'requested',
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);
        });
    }

    /**
     * True when the requested change exceeds the configured auto-approval thresholds and
     * therefore needs a higher role to approve it (enforced via policy/permission, not here).
     */
    public function requiresApproval(BillingAdjustment $adjustment): bool
    {
        if ($adjustment->type === 'write_off') {
            return true;
        }

        $amountThreshold = (string) $this->settings->get('billing.discount_approval_threshold_amount', 5000);
        $percentThreshold = (string) $this->settings->get('billing.discount_approval_threshold_percent', 10);

        $absoluteDifference = bccomp((string) $adjustment->difference, '0', 2) === -1
            ? bcmul((string) $adjustment->difference, '-1', 2)
            : (string) $adjustment->difference;

        if (bccomp($absoluteDifference, $amountThreshold, 2) === 1) {
            return true;
        }

        // Percentage is relative to the invoice total, not $adjustment->original_value — for a
        // brand-new discount that value starts at 0, which would make any discount "infinite
        // percent" (or divide-by-zero) if used as the denominator here.
        $invoiceTotal = (string) ($adjustment->invoice?->grand_total ?? '0');

        if (bccomp($invoiceTotal, '0', 2) > 0) {
            $percent = bcmul(bcdiv($absoluteDifference, $invoiceTotal, 6), '100', 2);

            return bccomp($percent, $percentThreshold, 2) === 1;
        }

        return false;
    }

    public function approve(BillingAdjustment $adjustment, User $approver, ?string $note = null): BillingAdjustment
    {
        return DB::transaction(function () use ($adjustment, $approver, $note) {
            if (! $adjustment->isPending()) {
                throw ValidationException::withMessages(['adjustment' => "Cannot approve an adjustment in '{$adjustment->status}' status."]);
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            if ($invoice = $adjustment->invoice) {
                $this->applyToInvoice($invoice, $adjustment);
            }

            $adjustment = $adjustment->refresh();

            event(new AdjustmentApproved($adjustment));
            $this->revenuePoster->postAdjustment($adjustment);

            return $adjustment;
        });
    }

    private function applyToInvoice(BillingInvoice $invoice, BillingAdjustment $adjustment): void
    {
        $difference = (string) $adjustment->difference;

        if ($adjustment->type === 'write_off') {
            $invoice->update([
                'status' => 'written_off',
                'due_amount' => '0.00',
                'written_off_by' => $adjustment->approved_by,
                'written_off_at' => now(),
            ]);

            return;
        }

        $updates = match ($adjustment->type) {
            'discount', 'price' => [
                'discount_amount' => bcadd((string) $invoice->discount_amount, $difference, 2),
                'grand_total' => bcsub((string) $invoice->grand_total, $difference, 2),
                'due_amount' => bcsub((string) $invoice->due_amount, $difference, 2),
            ],
            'credit' => [
                'due_amount' => bcsub((string) $invoice->due_amount, $difference, 2),
            ],
            'debit' => [
                'due_amount' => bcadd((string) $invoice->due_amount, $difference, 2),
                'grand_total' => bcadd((string) $invoice->grand_total, $difference, 2),
            ],
            default => [],
        };

        if (isset($updates['due_amount']) && bccomp($updates['due_amount'], '0', 2) === -1) {
            $updates['due_amount'] = '0.00';
        }

        if (! empty($updates)) {
            $grandTotal = $updates['grand_total'] ?? (string) $invoice->grand_total;
            $updates['status'] = $this->deriveStatus((string) $invoice->paid_amount, $grandTotal);
            $invoice->update($updates);
        }
    }

    private function deriveStatus(string $paidAmount, string $grandTotal): string
    {
        if (bccomp($paidAmount, '0', 2) <= 0) {
            return 'finalized';
        }

        return bccomp($paidAmount, $grandTotal, 2) >= 0 ? 'paid' : 'partially_paid';
    }
}
