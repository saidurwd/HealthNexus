<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimLine;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsuranceRemittance;
use App\Models\Insurance\InsuranceWriteoff;
use App\Models\User;
use App\Services\Billing\PaymentService;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Money from the payer: a remittance is recorded once and allocated across claims; each allocation posts a payment on the invoice (so Billing's
 * books agree), moves the claim to partly paid or paid, and uses up the policy's benefits. Writing off what a payer will not pay needs a second
 * person's approval.
 */
class InsuranceRemittanceService
{
    public function __construct(
        private readonly NumberSeriesService $numbers,
        private readonly PaymentService $payments,
        private readonly InsuranceClaimService $claims,
        private readonly InsurancePolicySettings $settings,
    ) {}

    public function record(InsurancePayer $payer, string $amount, string $receivedOn, User $by, ?string $payerReference = null, string $method = 'bank_transfer'): InsuranceRemittance
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount received must be above zero.']);
        }

        return InsuranceRemittance::create([
            'organization_id' => $payer->organization_id, 'payer_id' => $payer->id, 'method' => $method, 'amount' => $amount, 'received_on' => $receivedOn, 'payer_reference' => $payerReference,
            'remittance_number' => $this->numbers->next('insurance.remittance', $payer->organization_id, null, prefix: 'RMT', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by->id), 'recorded_by' => $by->id,
        ]);
    }

    /** Matches part of a remittance to a claim. The payment is posted on the invoice; nothing is allocated above what the remittance holds or the claim is owed. */
    public function allocate(InsuranceRemittance $remittance, InsuranceClaim $claim, string $paid, User $by, string $adjustment = '0.00', ?string $adjustmentReason = null): InsuranceRemittance
    {
        return DB::transaction(function () use ($remittance, $claim, $paid, $by, $adjustment, $adjustmentReason) {
            $rem = InsuranceRemittance::query()->lockForUpdate()->findOrFail($remittance->id);
            $c = InsuranceClaim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($c->payer_id !== $rem->payer_id || $c->organization_id !== $rem->organization_id) {
                throw ValidationException::withMessages(['claim' => 'This claim is for another payer.']);
            }
            if (! in_array($c->status, ['submitted', 'acknowledged', 'accepted', 'appealed', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['claim' => "A claim that is {$c->status} cannot receive a payment."]);
            }
            if (bccomp($paid, '0', 2) <= 0 && bccomp($adjustment, '0', 2) <= 0) {
                throw ValidationException::withMessages(['paid' => 'Enter the amount paid or the amount the payer disallowed.']);
            }
            if (bccomp($paid, $rem->unallocated(), 2) > 0) {
                throw ValidationException::withMessages(['paid' => "Only {$rem->unallocated()} of this remittance is left."]);
            }
            if (bccomp(bcadd($paid, $adjustment, 2), $c->outstanding(), 2) > 0) {
                throw ValidationException::withMessages(['paid' => "The payer owes only {$c->outstanding()} on this claim."]);
            }

            $paymentId = null;
            if (bccomp($paid, '0', 2) > 0) {
                $invoice = BillingInvoice::findOrFail($c->invoice_id);
                if (! in_array($invoice->status, ['finalized', 'partially_paid'], true) || bccomp($paid, $invoice->remainingDue(), 2) > 0) {
                    throw ValidationException::withMessages(['paid' => "The invoice has only {$invoice->remainingDue()} left to pay (the patient may have paid more than their share)."]);
                }
                $payment = $this->payments->collect($invoice, ['payment_method_id' => $this->payerMethod($c->organization_id)->id, 'amount' => $paid, 'transaction_reference' => $rem->payer_reference ?: $rem->remittance_number, 'notes' => "Claim {$c->claim_number}"], $by);
                $paymentId = $payment->id;
            }

            DB::table('insurance_remittance_lines')->insert([
                'organization_id' => $c->organization_id, 'remittance_id' => $rem->id, 'claim_id' => $c->id, 'paid_amount' => $paid, 'adjustment_amount' => $adjustment,
                'adjustment_reason' => $adjustmentReason, 'billing_payment_id' => $paymentId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $allocated = bcadd((string) $rem->allocated_amount, $paid, 2);
            $rem->update(['allocated_amount' => $allocated, 'status' => bccomp($allocated, (string) $rem->amount, 2) >= 0 ? 'allocated' : 'partly_allocated']);

            $first = bccomp((string) $c->paid_amount, '0', 2) === 0 && bccomp((string) $c->adjusted_amount, '0', 2) === 0;
            $c->update(['paid_amount' => bcadd((string) $c->paid_amount, $paid, 2), 'adjusted_amount' => bcadd((string) $c->adjusted_amount, $adjustment, 2)]);
            $this->spread($c, $paid);
            $first && $this->useBenefits($c);

            $settled = bccomp($c->refresh()->outstanding(), '0', 2) === 0;
            $this->claims->transition($c, $settled ? 'paid' : 'partially_paid', $by, "Remittance {$rem->remittance_number}", $paid);

            return $rem->refresh();
        });
    }

    public function requestWriteoff(InsuranceClaim $claim, string $amount, string $reason, User $by): InsuranceWriteoff
    {
        if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $claim->outstanding(), 2) > 0) {
            throw ValidationException::withMessages(['amount' => "A write-off must be above zero and no more than the {$claim->outstanding()} still owed."]);
        }
        if (! in_array($claim->status, ['denied', 'partially_paid', 'appealed'], true)) {
            throw ValidationException::withMessages(['claim' => 'Only a denied, appealed or part-paid claim can be written off.']);
        }

        return InsuranceWriteoff::create(['organization_id' => $claim->organization_id, 'claim_id' => $claim->id, 'amount' => $amount, 'reason' => $reason, 'requested_by' => $by->id]);
    }

    /** The approver must be a different person from the requester. */
    public function decideWriteoff(InsuranceWriteoff $writeoff, bool $approve, User $by): InsuranceWriteoff
    {
        return DB::transaction(function () use ($writeoff, $approve, $by) {
            $locked = InsuranceWriteoff::query()->lockForUpdate()->findOrFail($writeoff->id);
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages(['writeoff' => 'This write-off was already decided.']);
            }
            if ($this->settings->get('writeoff_requires_approval') && $locked->requested_by === $by->id) {
                throw ValidationException::withMessages(['writeoff' => 'A write-off must be approved by someone other than the person who asked for it.']);
            }
            $locked->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $by->id, 'decided_at' => now()]);
            if ($approve) {
                $claim = InsuranceClaim::query()->lockForUpdate()->findOrFail($locked->claim_id);
                if (bccomp((string) $locked->amount, $claim->outstanding(), 2) > 0) {
                    throw ValidationException::withMessages(['writeoff' => 'The claim is owed less than this now.']);
                }
                $claim->update(['adjusted_amount' => bcadd((string) $claim->adjusted_amount, (string) $locked->amount, 2)]);
                $this->claims->transition($claim, bccomp($claim->refresh()->outstanding(), '0', 2) === 0 ? 'written_off' : $claim->status, $by, "Write-off approved: {$locked->reason}", (string) $locked->amount);
            }

            return $locked->refresh();
        });
    }

    /** What the cashier should still ask the patient for, now that the payer's part is known. */
    public function patientDue(BillingInvoice $invoice): string
    {
        $claim = InsuranceClaim::query()->where('invoice_id', $invoice->id)->whereNotIn('status', ['cancelled', 'written_off'])->latest('id')->first();
        $payerOwes = $claim ? $claim->outstanding() : '0.00';
        $due = bcsub($invoice->remainingDue(), $payerOwes, 2);

        return bccomp($due, '0', 2) < 0 ? '0.00' : $due;
    }

    private function payerMethod(int $organizationId): BillingPaymentMethod
    {
        return BillingPaymentMethod::firstOrCreate(['organization_id' => $organizationId, 'branch_id' => null, 'code' => 'INS'], ['name' => 'Insurance / payer', 'type' => 'bank_transfer', 'is_active' => true]);
    }

    /** A payment is shared over the lines in proportion to what the payer owed on each. */
    private function spread(InsuranceClaim $claim, string $paid): void
    {
        $lines = InsuranceClaimLine::where('claim_id', $claim->id)->where('payer_share', '>', 0)->orderBy('id')->get();
        $total = (string) $lines->sum('payer_share');
        if ($lines->isEmpty() || bccomp($total, '0', 2) <= 0) {
            return;
        }
        $left = $paid;
        foreach ($lines as $i => $line) {
            $part = $i === $lines->count() - 1 ? $left : bcdiv(bcmul($paid, (string) $line->payer_share, 6), $total, 2);
            $part = bccomp($part, $left, 2) > 0 ? $left : $part;
            $line->update(['paid_amount' => bcadd((string) $line->paid_amount, $part, 2)]);
            $left = bcsub($left, $part, 2);
        }
    }

    /** The first payment on a claim counts the deductible and the amount against the policy's year. */
    private function useBenefits(InsuranceClaim $claim): void
    {
        $policy = $claim->policy_id ? BillingInsurancePolicy::query()->lockForUpdate()->find($claim->policy_id) : null;
        if ($policy) {
            $policy->update(['annual_used' => bcadd((string) $policy->annual_used, (string) $claim->payer_share, 2), 'deductible_met' => bcadd((string) $policy->deductible_met, (string) $claim->deductible_amount, 2)]);
        }
    }
}
