<?php

namespace App\Services\Portal;

use App\Contracts\Integration\PaymentGateway;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalSettlement;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Paying an invoice online (S06). Billing stays the only place that creates a payment: when — and only when — the aggregator says the money arrived, `PaymentService::collect`
 * posts it. The browser coming back is not proof; we ask the aggregator. The same idempotency key never takes the money twice.
 */
class PortalPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentService $payments,
        private readonly PortalSystemUser $system,
        private readonly PortalPolicy $policy,
        private readonly AuditLogger $audit,
    ) {}

    public function available(): bool
    {
        return $this->gateway->name() !== 'none';
    }

    /** @return Collection<int, BillingInvoice> */
    public function invoices(array $patientIds, bool $openOnly = false): Collection
    {
        return BillingInvoice::query()->whereIn('patient_id', $patientIds)->whereIn('status', $openOnly ? ['finalized', 'partially_paid'] : ['finalized', 'partially_paid', 'paid'])->orderByDesc('invoice_date')->orderByDesc('id')->limit(60)->get();
    }

    public function initiate(PatientPortalAccount $account, Patient $patient, BillingInvoice $invoice, string $method, string $idempotencyKey, ?string $amount = null): PortalPayment
    {
        $methods = (array) $this->policy->get('payments.methods', []);
        if (! in_array($method, $methods, true)) {
            throw new PortalException(__('Choose how you want to pay.'));
        }
        if (! $this->available()) {
            throw new PortalException(__('Online payment is not available right now. Please pay at the counter.'), 409);
        }
        $key = hash('sha256', $account->id.'|'.$idempotencyKey);

        $existing = PortalPayment::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            if ($existing->invoice_id !== $invoice->id) {
                throw new PortalException(__('This request key was already used for something else.'), 409);
            }

            return $existing;
        }

        $payment = DB::transaction(function () use ($account, $patient, $invoice, $method, $key, $amount) {
            $locked = BillingInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->patient_id !== $patient->id || ! in_array($locked->status, ['finalized', 'partially_paid'], true)) {
                throw new PortalException(__('This bill cannot be paid online.'), 409);
            }
            $due = $locked->remainingDue();
            $amount = $amount !== null && $amount !== '' ? number_format((float) $amount, 2, '.', '') : $due;
            if (bccomp($amount, (string) $this->policy->get('payments.minimum', '10.00'), 2) < 0 && bccomp($amount, $due, 2) !== 0) {
                throw new PortalException(__('The amount is too small.'));
            }
            if (bccomp($amount, $due, 2) > 0 || bccomp($amount, '0', 2) <= 0) {
                throw new PortalException(__('The amount is more than the bill.'));
            }
            $open = PortalPayment::query()->where('invoice_id', $locked->id)->whereIn('status', ['initiated', 'pending'])->where('created_at', '>', now()->subMinutes($this->policy->int('payments.pending_expires_minutes', 30)))->lockForUpdate()->first();
            if ($open) {
                throw new PortalException(__('A payment for this bill is already in progress. Please wait for it to finish.'), 409);
            }

            return PortalPayment::create(['organization_id' => $locked->organization_id, 'patient_id' => $patient->id, 'account_id' => $account->id, 'invoice_id' => $locked->id, 'amount' => $amount,
                'currency' => $locked->currency ?: (string) $this->policy->get('payments.currency', 'BDT'), 'gateway' => $this->gateway->name(), 'method' => $method, 'status' => 'initiated', 'idempotency_key' => $key, 'initiated_at' => now()]);
        });

        $result = $this->gateway->initiate(['amount' => (string) $payment->amount, 'currency' => $payment->currency, 'method' => $method, 'reference' => $invoice->invoice_number, 'idempotency_key' => $key, 'return_url' => url('/portal/pay/return/'.$payment->public_id)]);
        if (! $result->handled) {
            $payment->update(['status' => 'failed', 'failure_reason' => mb_substr($result->reason, 0, 200)]);
            throw new PortalException(__('The payment could not be started. Nothing was charged.'), 502);
        }
        $payment->update(['status' => 'pending', 'gateway_ref' => $result->data['gateway_ref'] ?? null, 'redirect_url' => $result->data['redirect_url'] ?? null]);
        $this->audit->log('CREATE', PortalPayment::class, $payment->id, null, ['invoice_id' => $invoice->id, 'amount' => (string) $payment->amount, 'account_id' => $account->id]);

        return $payment->refresh();
    }

    /** Asks the aggregator and, if the money really arrived, posts it to Billing. Safe to call many times (return page, webhook, sweep). */
    public function confirm(PortalPayment $payment): PortalPayment
    {
        if (in_array($payment->status, ['succeeded', 'failed', 'cancelled'], true) || ! $payment->gateway_ref) {
            return $payment;
        }
        $result = $this->gateway->verify($payment->gateway_ref);
        if (! $result->handled) {
            return $payment;
        }
        $status = (string) ($result->data['status'] ?? 'pending');
        if ($status === 'failed') {
            $payment->update(['status' => 'failed', 'failure_reason' => 'declined by the payment provider', 'completed_at' => now()]);

            return $payment;
        }
        if ($status !== 'succeeded') {
            return $payment;
        }
        if (bccomp(number_format((float) ($result->data['amount'] ?? 0), 2, '.', ''), (string) $payment->amount, 2) !== 0) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'amount taken differs from amount asked', 'completed_at' => now()]);
            $this->audit->log('UPDATE', PortalPayment::class, $payment->id, null, ['alert' => 'amount mismatch']);

            return $payment;
        }

        return DB::transaction(function () use ($payment) {
            $payment = PortalPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'succeeded') {
                return $payment;
            }
            $invoice = BillingInvoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $user = $this->system->for($payment->organization_id);
            if (! in_array($invoice->status, ['finalized', 'partially_paid'], true) || bccomp((string) $payment->amount, $invoice->remainingDue(), 2) > 0) {
                // Paid at the counter while the patient was paying online: keep the facts, flag it for the accountant to refund.
                $payment->update(['status' => 'succeeded', 'completed_at' => now(), 'failure_reason' => 'bill already settled — refund needed']);

                return $payment;
            }
            $billing = $this->payments->collect($invoice, ['payment_method_id' => $this->method($payment->organization_id)->id, 'amount' => (string) $payment->amount, 'transaction_reference' => $payment->gateway_ref, 'currency' => $payment->currency, 'notes' => 'Online payment ('.$payment->method.')'], $user);
            $payment->update(['status' => 'succeeded', 'completed_at' => now(), 'billing_payment_id' => $billing->id, 'failure_reason' => null]);
            PortalNotification::create(['organization_id' => $payment->organization_id, 'patient_id' => $payment->patient_id, 'kind' => 'payment', 'title' => __('Payment received'), 'body' => __('Thank you. Your receipt is ready.'), 'link' => '/portal/billing']);
            $this->audit->log('UPDATE', PortalPayment::class, $payment->id, ['status' => 'pending'], ['status' => 'succeeded', 'billing_payment_id' => $billing->id]);

            return $payment;
        });
    }

    public function handleCallback(string $gatewayRef): ?PortalPayment
    {
        $payment = PortalPayment::query()->where('gateway_ref', $gatewayRef)->first();

        return $payment ? $this->confirm($payment) : null;
    }

    /** Sweeps open payments: asks again, and gives up on the ones that never completed. */
    public function sweep(): array
    {
        $done = $expired = 0;
        PortalPayment::query()->whereIn('status', ['initiated', 'pending'])->orderBy('id')->each(function (PortalPayment $p) use (&$done, &$expired) {
            $p = $this->confirm($p);
            if ($p->status === 'succeeded') {
                $done++;
            } elseif (in_array($p->status, ['initiated', 'pending'], true) && $p->created_at->lt(now()->subMinutes($this->policy->int('payments.pending_expires_minutes', 30)))) {
                $p->update(['status' => 'expired', 'failure_reason' => 'not completed in time', 'completed_at' => now()]);
                $expired++;
            }
        });

        return ['confirmed' => $done, 'expired' => $expired];
    }

    /** Receipt for a payment the patient made online (only theirs). */
    public function billingPayment(PortalPayment $payment): ?BillingPayment
    {
        return $payment->billing_payment_id ? BillingPayment::query()->with(['receipt', 'invoice'])->find($payment->billing_payment_id) : null;
    }

    /**
     * Matches the aggregator's settlement file to what we recorded. Lines: [{gateway_ref, amount, fee?, paid_on?}].
     *
     * @param  list<array{gateway_ref:string,amount:string|float,fee?:string|float,paid_on?:string}>  $lines
     */
    public function reconcile(int $organizationId, string $gateway, string $periodFrom, string $periodTo, array $lines, User $by, ?string $fileName = null): PortalSettlement
    {
        return DB::transaction(function () use ($organizationId, $gateway, $periodFrom, $periodTo, $lines, $by, $fileName) {
            $settlement = PortalSettlement::create(['organization_id' => $organizationId, 'gateway' => $gateway, 'file_name' => $fileName, 'period_from' => $periodFrom, 'period_to' => $periodTo, 'imported_by' => $by->id]);
            $matched = $differences = 0;
            $total = $fees = '0.00';
            $seen = [];
            foreach ($lines as $line) {
                $ref = (string) $line['gateway_ref'];
                $amount = number_format((float) $line['amount'], 2, '.', '');
                $fee = number_format((float) ($line['fee'] ?? 0), 2, '.', '');
                $payment = PortalPayment::query()->where('organization_id', $organizationId)->where('gateway_ref', $ref)->first();
                $result = match (true) {
                    isset($seen[$ref]) => 'unknown_payment', // listed twice in the file
                    $payment === null => 'unknown_payment',
                    $payment->status !== 'succeeded' => 'not_succeeded',
                    bccomp((string) $payment->amount, $amount, 2) !== 0 => 'amount_differs',
                    default => 'matched',
                };
                $seen[$ref] = true;
                $settlement->lines()->create(['gateway_ref' => $ref, 'amount' => $amount, 'fee' => $fee, 'paid_on' => $line['paid_on'] ?? null, 'result' => $result, 'portal_payment_id' => $payment?->id]);
                if ($result === 'matched') {
                    $matched++;
                    $payment->update(['reconciled_at' => now()]);
                } else {
                    $differences++;
                }
                $total = bcadd($total, $amount, 2);
                $fees = bcadd($fees, $fee, 2);
            }
            $settlement->update(['line_count' => count($lines), 'matched' => $matched, 'differences' => $differences, 'total' => $total, 'fees' => $fees]);
            $this->audit->log('CREATE', PortalSettlement::class, $settlement->id, null, ['lines' => count($lines), 'differences' => $differences]);

            return $settlement;
        });
    }

    /** Succeeded online payments in the period that the aggregator's file did not mention. */
    public function missingFromSettlement(int $organizationId, string $from, string $to): Collection
    {
        return PortalPayment::query()->where('organization_id', $organizationId)->where('status', 'succeeded')->whereNull('reconciled_at')->whereBetween(DB::raw('date(completed_at)'), [$from, $to])->orderBy('completed_at')->get();
    }

    private function method(int $organizationId): BillingPaymentMethod
    {
        return BillingPaymentMethod::query()->firstOrCreate(['organization_id' => $organizationId, 'branch_id' => null, 'code' => (string) $this->policy->get('payments.method_code', 'online')], ['name' => 'Online payment', 'type' => 'online', 'is_active' => true]);
    }

    public static function newKey(): string
    {
        return (string) Str::uuid();
    }
}
