<?php

namespace Tests\Feature\Insurance;

use App\Events\Billing\InvoiceFinalized;
use App\Models\Insurance\InsuranceClaim;
use App\Services\Billing\InvoiceLifecycleService;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsuranceReportService;
use App\Services\Insurance\InsuranceSubmissionService;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\Storage;

class InsuranceReportsAndHooksTest extends InsuranceTestCase
{
    public function test_finalising_an_invoice_for_an_insured_patient_drafts_one_claim_and_never_two(): void
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $invoice = $this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $policy);
        $invoice->update(['status' => 'draft']);

        app(InvoiceLifecycleService::class)->finalize($invoice, $this->user);
        $this->assertSame(1, InsuranceClaim::where('invoice_id', $invoice->id)->count());
        $this->assertSame('draft', InsuranceClaim::where('invoice_id', $invoice->id)->value('status'));

        event(new InvoiceFinalized($invoice->refresh()));
        $this->assertSame(1, InsuranceClaim::where('invoice_id', $invoice->id)->count(), 'repeating the event does not repeat the claim');
    }

    public function test_an_uninsured_invoice_and_a_module_that_is_off_make_no_claim(): void
    {
        $invoice = $this->invoice($this->patient(), [['OPD', 'CONSULT', '500.00']]);
        event(new InvoiceFinalized($invoice));
        $this->assertSame(0, InsuranceClaim::count());

        $patient = $this->patient();
        $insured = $this->invoice($patient, [['OPD', 'CONSULT', '500.00']], $this->policy($patient));
        app(ModuleManager::class)->set('insurance', false, $this->organization->id);
        event(new InvoiceFinalized($insured));
        $this->assertSame(0, InsuranceClaim::count());
    }

    public function test_aging_buckets_what_each_payer_still_owes_by_days_since_submission(): void
    {
        Storage::fake('local');
        $claims = app(InsuranceClaimService::class);
        $submissions = app(InsuranceSubmissionService::class);
        $payerPlan = $this->plan(null, ['copay_percent' => 0]);
        foreach ([5, 45, 120] as $daysAgo) {
            $patient = $this->patient();
            $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $this->policy($patient, $payerPlan)), $this->user);
            $claim->documents()->update(['received' => true]);
            $claims->markReady($claim, $this->user);
            $submissions->markSubmitted($submissions->exportBatch($claim->payer, [$claim->refresh()], $this->user), 'R', $this->user);
            $claim->refresh()->update(['submitted_at' => now()->subDays($daysAgo)]);
        }

        $aging = app(InsuranceReportService::class)->aging($this->organization->id);

        $this->assertCount(1, $aging);
        $this->assertSame('3000.00', $aging[0]['total']);
        $this->assertSame(['0–30' => '1000.00', '31–60' => '1000.00', '61–90' => '0.00', '91+' => '1000.00'], $aging[0]['buckets']);
    }

    public function test_the_dashboard_counts_claims_by_status_and_the_denial_rate(): void
    {
        $claims = app(InsuranceClaimService::class);
        $patient = $this->patient();
        $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $this->policy($patient)), $this->user);
        $dash = app(InsuranceReportService::class)->dashboard($this->organization->id);

        $this->assertSame(1, $dash['by_status']['draft']['count']);
        $this->assertSame(0.0, $dash['denial_rate_percent']);
        $this->assertSame(0, $dash['pending_preauths']);
        $this->assertNotNull($claim);
    }
}
