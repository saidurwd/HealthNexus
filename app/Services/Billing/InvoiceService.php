<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingInvoice;
use App\Models\Patient;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly BillingCalculationService $calculator,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{company_id:int,branch_id:int,invoice_type?:string,currency?:string,encounter_id?:int|null,corporate_id?:int|null,corporate_contract_id?:int|null,insurance_policy_id?:int|null,patient_category?:string|null,billing_party_type?:string|null,billing_party_id?:int|null,due_date?:string|null,notes?:string|null}  $context
     */
    public function createDraft(Patient $patient, array $context, User $user): BillingInvoice
    {
        return DB::transaction(function () use ($patient, $context, $user) {
            return BillingInvoice::create([
                'company_id' => $context['company_id'],
                'branch_id' => $context['branch_id'],
                'patient_id' => $patient->id,
                'encounter_id' => $context['encounter_id'] ?? null,
                'corporate_id' => $context['corporate_id'] ?? null,
                'corporate_contract_id' => $context['corporate_contract_id'] ?? null,
                'insurance_policy_id' => $context['insurance_policy_id'] ?? null,
                'invoice_number' => null,
                'invoice_type' => $context['invoice_type'] ?? 'opd',
                'status' => 'draft',
                'currency' => $context['currency'] ?? $this->settings->get('billing.currency', 'BDT'),
                'patient_category' => $context['patient_category'] ?? null,
                'subtotal' => '0.00',
                'discount_type' => 'none',
                'discount_amount' => '0.00',
                'tax_amount' => '0.00',
                'rounding_amount' => '0.00',
                'grand_total' => '0.00',
                'paid_amount' => '0.00',
                'due_amount' => '0.00',
                'invoice_date' => now()->toDateString(),
                'due_date' => $context['due_date'] ?? null,
                'billing_party_type' => $context['billing_party_type'] ?? 'patient',
                'billing_party_id' => $context['billing_party_id'] ?? $patient->id,
                'notes' => $context['notes'] ?? null,
                'version' => 1,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * Converts uninvoiced charges into invoice items, preserving the price/tax/discount already
     * calculated at charge time — never recalculated against today's price list.
     *
     * @param  Collection<int, BillingCharge>  $charges
     */
    public function addCharges(BillingInvoice $invoice, Collection $charges): BillingInvoice
    {
        return DB::transaction(function () use ($invoice, $charges) {
            $this->assertMutable($invoice);

            foreach ($charges as $charge) {
                if ($charge->isBilled() || $charge->isCancelled()) {
                    throw ValidationException::withMessages(['charges' => "Charge #{$charge->id} is not available to bill (already billed or cancelled)."]);
                }

                $invoice->items()->create([
                    'charge_id' => $charge->id,
                    'billing_item_id' => $charge->billing_item_id,
                    'description' => $charge->billingItem?->name ?? 'Service',
                    'quantity' => $charge->quantity,
                    'unit_price' => $charge->unit_price,
                    'gross_amount' => $charge->gross_amount,
                    'discount_type' => $charge->discount_type,
                    'discount_amount' => $charge->discount_amount,
                    'tax_rate' => $charge->billingItem?->taxCategory?->rate ?? '0',
                    'tax_amount' => $charge->tax_amount,
                    'net_amount' => $charge->net_amount,
                ]);

                $charge->update(['status' => 'billed', 'billed_at' => now()]);
            }

            return $this->recalculate($invoice);
        });
    }

    /**
     * Recomputes totals purely from the invoice's line items (each of which already carries its
     * own resolved price/discount/tax from charge time). This does not apply a whole-invoice
     * manual discount — that is a distinct, audited action handled by AdjustmentService, which
     * layers an approved delta on top rather than being re-derived here on every recalculation.
     */
    public function recalculate(BillingInvoice $invoice): BillingInvoice
    {
        return DB::transaction(function () use ($invoice) {
            $this->assertMutable($invoice);

            $lines = $invoice->items()->get()->map(fn ($item) => [
                'gross_amount' => (string) $item->gross_amount,
                'discount_amount' => (string) $item->discount_amount,
                'tax_amount' => (string) $item->tax_amount,
                'net_amount' => (string) $item->net_amount,
            ]);

            $totals = $this->calculator->calculateInvoiceTotals(
                $lines,
                'none',
                '0',
                $this->settings->get('billing.rounding_mode', 'nearest'),
                (int) $this->settings->get('billing.rounding_precision', 2),
            );

            $invoice->update([
                'subtotal' => $totals['subtotal'],
                'discount_type' => $invoice->discount_type ?? 'none',
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'rounding_amount' => $totals['rounding_amount'],
                'grand_total' => $totals['grand_total'],
                'due_amount' => bcsub($totals['grand_total'], (string) $invoice->paid_amount, 2),
            ]);

            return $invoice->refresh();
        });
    }

    private function assertMutable(BillingInvoice $invoice): void
    {
        if (! $invoice->isMutable()) {
            throw ValidationException::withMessages(['invoice' => 'This invoice is finalized and can no longer be edited. Use cancel, refund, write-off, or an adjustment instead.']);
        }
    }
}
