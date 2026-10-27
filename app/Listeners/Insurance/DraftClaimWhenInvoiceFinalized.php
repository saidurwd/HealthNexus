<?php

namespace App\Listeners\Insurance;

use App\Events\Billing\InvoiceFinalized;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsuranceClaim;
use App\Models\User;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\ModuleManager;
use Illuminate\Validation\ValidationException;

/**
 * Every module's charges end up on an invoice, so this one hook is how IPD, ED, OT, pharmacy, lab, radiology and the blood bank reach the
 * insurance desk: an invoice finalised for a patient with a payer and plan gets a DRAFT claim (never sent without a person). Safe to repeat.
 */
class DraftClaimWhenInvoiceFinalized
{
    public function __construct(private readonly InsuranceClaimService $claims, private readonly ModuleManager $modules) {}

    public function handle(InvoiceFinalized $event): void
    {
        $invoice = $event->invoice;
        if (! $invoice->insurance_policy_id || ! $this->modules->isEnabled('insurance', $invoice->organization_id)) {
            return;
        }
        $policy = BillingInsurancePolicy::find($invoice->insurance_policy_id);
        if (! $policy || ! $policy->payer_id || ! $policy->plan_id || InsuranceClaim::where('invoice_id', $invoice->id)->where('payer_id', $policy->payer_id)->where('status', '!=', 'cancelled')->exists()) {
            return;
        }
        $by = User::find($invoice->finalized_by ?? $invoice->created_by);
        if (! $by) {
            return;
        }

        try {
            $this->claims->generate($invoice, $by, $policy);
        } catch (ValidationException) {
            // The invoice cannot be claimed as it stands; the insurance desk makes the claim by hand when it is fixed.
        }
    }
}
