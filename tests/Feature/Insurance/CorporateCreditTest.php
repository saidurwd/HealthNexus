<?php

namespace Tests\Feature\Insurance;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingInvoice;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Insurance\CorporateCreditService;
use Illuminate\Validation\ValidationException;

class CorporateCreditTest extends InsuranceTestCase
{
    private function corporate(string $limit = '10000.00'): BillingCorporate
    {
        return BillingCorporate::create(['organization_id' => $this->organization->id, 'name' => 'Acme Ltd', 'code' => 'ACME'.fake()->unique()->numerify('##'), 'credit_limit' => $limit, 'payment_terms_days' => 30, 'billing_cycle' => 'monthly', 'status' => 'active']);
    }

    private function corporateInvoice(BillingCorporate $corporate, string $amount, string $status = 'finalized'): BillingInvoice
    {
        $invoice = $this->invoice($this->patient(), [['SVC', 'CONSULT', $amount]]);
        $invoice->update(['corporate_id' => $corporate->id, 'status' => $status]);

        return $invoice->refresh();
    }

    public function test_the_position_counts_what_is_owed_and_a_bill_over_the_limit_is_blocked(): void
    {
        $corporate = $this->corporate('10000.00');
        $this->corporateInvoice($corporate, '7000.00');
        $service = app(CorporateCreditService::class);

        $this->assertSame(['limit' => '10000.00', 'owed' => '7000.00', 'available' => '3000.00'], $service->position($corporate));
        $service->assertCanBill($corporate, '3000.00');
        $this->expectException(ValidationException::class);
        $service->assertCanBill($corporate, '3000.01');
    }

    public function test_finalising_a_corporate_invoice_over_the_limit_is_refused_but_a_normal_invoice_is_not(): void
    {
        $corporate = $this->corporate('5000.00');
        $this->corporateInvoice($corporate, '4000.00');
        $draft = $this->corporateInvoice($corporate, '2000.00', 'draft');
        $normal = $this->invoice($this->patient(), [['SVC', 'CONSULT', '9000.00']]);
        $normal->update(['status' => 'draft']);

        app(InvoiceLifecycleService::class)->finalize($normal, $this->user);
        $this->assertSame('finalized', $normal->refresh()->status);

        $this->expectException(ValidationException::class);
        app(InvoiceLifecycleService::class)->finalize($draft, $this->user);
    }

    public function test_a_statement_gathers_the_period_once_is_issued_with_a_due_date_and_paid_down_oldest_first(): void
    {
        $corporate = $this->corporate('100000.00');
        $a = $this->corporateInvoice($corporate, '3000.00');
        $b = $this->corporateInvoice($corporate, '2000.00');
        $service = app(CorporateCreditService::class);

        $batch = $service->createBatch($corporate, now()->startOfMonth()->toDateString(), now()->toDateString(), $this->user);
        $this->assertSame(2, $batch->invoice_count);
        $this->assertSame('5000.00', (string) $batch->total_amount);
        $this->assertStringStartsWith('CRP-', $batch->batch_number);

        try {
            $service->createBatch($corporate, now()->startOfMonth()->toDateString(), now()->toDateString(), $this->user);
            $this->fail('the same invoices were put on a second statement');
        } catch (ValidationException) {
        }
        try {
            $service->receivePayment($batch, '100.00', $this->user);
            $this->fail('paid before issuing');
        } catch (ValidationException) {
        }

        $batch = $service->issue($batch, $this->user);
        $this->assertSame(now()->addDays(30)->toDateString(), $batch->due_on->toDateString());

        $batch = $service->receivePayment($batch, '3500.00', $this->user, 'CHQ-9');
        $this->assertSame('issued', $batch->status);
        $this->assertSame('paid', $a->refresh()->status);
        $this->assertSame('partially_paid', $b->refresh()->status);
        $this->assertSame('1500.00', (string) $b->due_amount);

        $batch = $service->receivePayment($batch, '1500.00', $this->user);
        $this->assertSame('settled', $batch->status);
        $this->assertSame('paid', $b->refresh()->status);

        $this->expectException(ValidationException::class);
        $service->receivePayment($batch, '1.00', $this->user);
    }
}
