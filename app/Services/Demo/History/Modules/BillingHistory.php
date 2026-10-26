<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingPaymentMethod;
use App\Models\Patient;
use App\Services\Billing\CashierSessionService;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\RefundService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns every charge the clinical history created into invoices, payments and receipts, one cashier session per working day — most paid in
 * full, some partly, some left owing, a few refunded. Runs last, because it bills what the other modules charged.
 */
class BillingHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoiceLifecycleService $lifecycle,
        private readonly PaymentService $payments,
        private readonly RefundService $refunds,
        private readonly CashierSessionService $sessions,
    ) {}

    public function name(): string
    {
        return 'Billing';
    }

    public function module(): string
    {
        return 'billing';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $cashiers = $context->staff('cashier');
        $methods = BillingPaymentMethod::where('organization_id', $orgId)->where('is_active', true)->get();
        $recent = $context->to->startOfDay();
        $counts = ['invoices' => 0, 'paid' => 0, 'partial' => 0, 'unpaid' => 0, 'refunds' => 0, 'sessions' => 0];

        $dates = BillingCharge::query()->where('organization_id', $orgId)->where('status', 'pending')->whereNull('billed_at')->get(['charged_at'])
            ->map(fn ($c) => CarbonImmutable::parse($c->charged_at)->toDateString())->unique()->sort()->values();

        foreach ($dates as $date) {
            $day = CarbonImmutable::parse($date);
            $cashier = $context->pick($cashiers);
            $session = $day->lt($recent) ? $context->at($day->setTime(8, 0), fn () => $this->sessions->open($cashier, $orgId, $context->branch->id, '5000.00')) : null;
            $session && $counts['sessions']++;

            $byPatient = BillingCharge::query()->where('organization_id', $orgId)->where('status', 'pending')->whereNull('billed_at')->whereDate('charged_at', $date)->orderBy('charged_at')->get()->groupBy('patient_id');
            foreach ($byPatient as $patientId => $charges) {
                $at = CarbonImmutable::parse($charges->max('charged_at'))->addMinutes(20);
                $this->invoice($context, Patient::findOrFail($patientId), $charges, $at, $cashier, $session, $methods, $day->lt($recent), $counts);
            }

            if ($session) {
                $context->at($day->setTime(20, 0), function () use ($session, $cashier) {
                    $expected = $this->sessions->reconcile($session->refresh())['expected_closing'];
                    $this->sessions->close($session->refresh(), $expected, 'Cash counted and matched', $cashier);
                });
            }
        }

        return sprintf('%d invoices (%d paid, %d part-paid, %d unpaid), %d refunds, %d cashier sessions', $counts['invoices'], $counts['paid'], $counts['partial'], $counts['unpaid'], $counts['refunds'], $counts['sessions']);
    }

    /** @param Collection<int, BillingCharge> $charges */
    private function invoice(HistoryContext $context, Patient $patient, $charges, CarbonImmutable $at, $cashier, $session, $methods, bool $settle, array &$counts): void
    {
        $eventTypes = BillingItem::query()->whereIn('id', $charges->pluck('billing_item_id'))->pluck('clinical_event_type');
        $inpatient = $eventTypes->contains(fn ($t) => str_contains((string) $t, 'ipd') || str_contains((string) $t, 'icu') || str_contains((string) $t, 'ot_'));
        $invoice = $context->at($at, function () use ($context, $patient, $charges, $inpatient, $cashier) {
            $invoice = $this->invoices->createDraft($patient, ['organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'invoice_type' => $inpatient ? 'ipd' : 'opd', 'encounter_id' => $charges->first()->encounter_id], $cashier);
            $invoice = $this->invoices->addCharges($invoice, new Collection($charges->all()));

            return $this->lifecycle->finalize($invoice, $cashier);
        });
        $counts['invoices']++;

        $roll = mt_rand(1, 100);
        if (! $settle || $roll > 90 || bccomp((string) $invoice->grand_total, '0', 2) <= 0) {
            $counts['unpaid']++;

            return;
        }
        $method = $session ? $context->pick($methods) : $methods->first();
        $amount = $roll <= 75 ? (string) $invoice->grand_total : bcdiv((string) $invoice->grand_total, '2', 2);
        $payment = $context->at($at->addMinutes(10), fn () => $this->payments->collect($invoice->refresh(), ['payment_method_id' => $method->id, 'amount' => $amount, 'transaction_reference' => $method->type === 'cash' ? null : 'TXN'.mt_rand(100000, 999999)], $cashier, $session));
        $roll <= 75 ? $counts['paid']++ : $counts['partial']++;

        if ($context->chance(2) && $payment instanceof BillingPayment) {
            $refund = $context->at($at->addHours(2), fn () => $this->refunds->request($payment, bcdiv((string) $payment->amount, '4', 2), 'Charged for a service not provided', $cashier));
            $manager = $context->actor;
            $context->at($at->addHours(3), fn () => $this->refunds->approve($refund, $manager, 'Approved'));
            $context->at($at->addHours(4), fn () => $this->refunds->process($refund->refresh(), $cashier, null));
            $counts['refunds']++;
        }
    }
}
