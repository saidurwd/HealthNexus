<?php

namespace Tests\Feature\Portal;

use App\Models\Billing\BillingPayment;
use App\Models\Portal\PortalPayment;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPaymentService;
use Illuminate\Support\Str;

class PortalPaymentTest extends PortalTestCase
{
    private function svc(): PortalPaymentService
    {
        return app(PortalPaymentService::class);
    }

    public function test_paying_online_posts_one_payment_to_billing_and_settles_the_invoice(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $invoice = $this->invoice($patient, '1000.00');

        $payment = $this->svc()->initiate($account, $patient, $invoice, 'bkash', 'key-1');
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->gateway_ref);
        $this->assertSame(0, BillingPayment::query()->count(), 'nothing is posted until the provider confirms');

        $done = $this->svc()->confirm($payment);
        $this->assertSame('succeeded', $done->status);
        $this->assertSame(1, BillingPayment::query()->count());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('1000.00', (string) $invoice->fresh()->paid_amount);
        $this->assertNotNull(BillingPayment::query()->first()->receipt);

        $this->svc()->confirm($done);
        $this->svc()->confirm($payment->fresh());
        $this->assertSame(1, BillingPayment::query()->count(), 'confirming again never pays twice');
    }

    public function test_the_same_key_never_takes_the_money_twice(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $invoice = $this->invoice($patient);

        $a = $this->svc()->initiate($account, $patient, $invoice, 'bkash', 'same-key');
        $b = $this->svc()->initiate($account, $patient, $invoice, 'bkash', 'same-key');
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, PortalPayment::query()->count());
    }

    public function test_only_one_payment_can_be_in_progress_for_a_bill(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $invoice = $this->invoice($patient);
        $this->svc()->initiate($account, $patient, $invoice, 'bkash', 'k1');

        $this->expectException(PortalException::class);
        $this->svc()->initiate($account, $patient, $invoice, 'nagad', 'k2');
    }

    public function test_a_part_payment_leaves_the_rest_due(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient, '1000.00');
        $p = $this->svc()->initiate($this->account($patient), $patient, $invoice, 'card', 'k1', '400.00');
        $this->svc()->confirm($p);

        $this->assertSame('partially_paid', $invoice->fresh()->status);
        $this->assertSame('600.00', $invoice->fresh()->remainingDue());
    }

    public function test_more_than_the_bill_and_unknown_methods_are_refused(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $invoice = $this->invoice($patient, '500.00');
        foreach ([['bkash', '501.00'], ['paypal', null], ['bkash', '0']] as [$method, $amount]) {
            try {
                $this->svc()->initiate($account, $patient, $invoice, $method, Str::random(8), $amount);
                $this->fail("accepted $method $amount");
            } catch (PortalException) {
            }
        }
        $this->assertSame(0, PortalPayment::query()->count());
    }

    public function test_someone_elses_bill_or_a_bill_not_open_cannot_be_paid(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $other = $this->patient();

        foreach ([$this->invoice($other), $this->invoice($patient, '100.00', '100.00', 'paid'), $this->invoice($patient, '100.00', '0.00', 'draft')] as $invoice) {
            try {
                $this->svc()->initiate($account, $patient, $invoice, 'bkash', Str::random(8));
                $this->fail('paid a bill that is not payable');
            } catch (PortalException) {
            }
        }
        $this->assertSame(0, PortalPayment::query()->count());
    }

    public function test_a_declined_payment_posts_nothing(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient);
        $invoice->update(['invoice_number' => 'INV-X-FAIL']);
        $p = $this->svc()->confirm($this->svc()->initiate($this->account($patient), $patient, $invoice, 'bkash', 'k1'));

        $this->assertSame('failed', $p->status);
        $this->assertSame(0, BillingPayment::query()->count());
        $this->assertSame('finalized', $invoice->fresh()->status);
    }

    public function test_a_payment_still_pending_stays_pending_and_then_expires(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient);
        $invoice->update(['invoice_number' => 'INV-X-HOLD']);
        $p = $this->svc()->initiate($this->account($patient), $patient, $invoice, 'bkash', 'k1');
        $this->assertSame('pending', $this->svc()->confirm($p)->status);

        $this->travel(31)->minutes();
        $this->assertSame(['confirmed' => 0, 'expired' => 1], $this->svc()->sweep());
        $this->assertSame('expired', $p->fresh()->status);
    }

    public function test_when_the_bill_was_paid_at_the_counter_meanwhile_the_online_money_is_flagged_not_posted_twice(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient, '500.00');
        $p = $this->svc()->initiate($this->account($patient), $patient, $invoice, 'bkash', 'k1');
        $invoice->update(['status' => 'paid', 'paid_amount' => '500.00', 'due_amount' => '0.00']);

        $done = $this->svc()->confirm($p);
        $this->assertSame('succeeded', $done->status);
        $this->assertNull($done->billing_payment_id);
        $this->assertStringContainsString('refund', $done->failure_reason);
        $this->assertSame(0, BillingPayment::query()->count());
    }

    public function test_the_provider_callback_is_checked_again_with_the_provider_not_believed(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient);
        $invoice->update(['invoice_number' => 'INV-X-HOLD']);
        $p = $this->svc()->initiate($this->account($patient), $patient, $invoice, 'bkash', 'k1');

        $this->postJson('/api/v1/portal/payments/callback', ['gateway_ref' => $p->gateway_ref])->assertOk()->assertJsonPath('data.status', 'pending');
        $this->assertSame(0, BillingPayment::query()->count());
        $this->postJson('/api/v1/portal/payments/callback', ['gateway_ref' => 'SBX-UNKNOWN'])->assertOk()->assertJsonPath('data.status', null);
    }

    public function test_without_a_payment_provider_online_payment_is_unavailable_but_the_bill_can_still_be_seen(): void
    {
        config(['portal.payments.driver' => 'null']);
        $patient = $this->patient();
        $headers = $this->bearer($this->account($patient));
        $invoice = $this->invoice($patient);

        $this->getJson('/api/v1/portal/invoices', $headers)->assertOk()->assertJsonPath('meta.payments_enabled', false)->assertJsonPath('data.0.number', $invoice->invoice_number);
        $this->postJson("/api/v1/portal/invoices/{$invoice->public_id}/pay", ['method' => 'bkash'], $headers + ['Idempotency-Key' => 'k'])->assertStatus(409);
    }

    public function test_the_api_needs_an_idempotency_key_for_paying(): void
    {
        $patient = $this->patient();
        $invoice = $this->invoice($patient);
        $this->postJson("/api/v1/portal/invoices/{$invoice->public_id}/pay", ['method' => 'bkash'], $this->bearer($this->account($patient)))->assertStatus(422);
    }

    public function test_the_settlement_file_is_matched_and_every_difference_is_reported(): void
    {
        $patient = $this->patient();
        $account = $this->account($patient);
        $a = $this->svc()->confirm($this->svc()->initiate($account, $patient, $this->invoice($patient, '300.00'), 'bkash', 'k1'));
        $b = $this->svc()->confirm($this->svc()->initiate($account, $patient, $this->invoice($patient, '200.00'), 'bkash', 'k2'));
        $c = $this->svc()->confirm($this->svc()->initiate($account, $patient, $this->invoice($patient, '100.00'), 'bkash', 'k3')); // not in the file

        $settlement = $this->svc()->reconcile($this->organization->id, 'sandbox', '2026-09-01', '2026-09-30', [
            ['gateway_ref' => $a->gateway_ref, 'amount' => '300.00', 'fee' => '4.50'],
            ['gateway_ref' => $b->gateway_ref, 'amount' => '190.00'],
            ['gateway_ref' => 'SBX-GHOST', 'amount' => '50.00'],
        ], $this->actor(['portal.payment.reconcile']));

        $this->assertSame(3, $settlement->line_count);
        $this->assertSame(1, $settlement->matched);
        $this->assertSame(2, $settlement->differences);
        $this->assertEqualsCanonicalizing(['matched', 'amount_differs', 'unknown_payment'], $settlement->lines->pluck('result')->all());
        $this->assertSame('4.50', (string) $settlement->fees);
        $this->assertNotNull($a->fresh()->reconciled_at);
        $this->assertNull($b->fresh()->reconciled_at);
        $this->assertSame([$c->id, $b->id], $this->svc()->missingFromSettlement($this->organization->id, '2026-09-01', '2026-09-30')->pluck('id')->sortDesc()->values()->all());
    }
}
