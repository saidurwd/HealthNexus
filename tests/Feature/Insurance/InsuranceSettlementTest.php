<?php

namespace Tests\Feature\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInvoice;
use App\Models\Insurance\InsuranceClaim;
use App\Models\Patient;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsuranceQueryService;
use App\Services\Insurance\InsuranceRemittanceService;
use App\Services\Insurance\InsuranceSubmissionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InsuranceSettlementTest extends InsuranceTestCase
{
    private function readyClaim(?Patient $patient = null, array $lines = [['OPD', 'CONSULT', '1000.00'], ['CBC', 'LAB', '2000.00']], ?array $planOverride = null): InsuranceClaim
    {
        $patient ??= $this->patient();
        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, $lines, $this->policy($patient, $this->plan(null, $planOverride ?? []))), $this->user);
        $claim->documents()->update(['received' => true]);
        $this->assertSame([], $claims->markReady($claim, $this->user));

        return $claim->refresh();
    }

    private function submit(InsuranceClaim $claim): InsuranceClaim
    {
        $submissions = app(InsuranceSubmissionService::class);
        $batch = $submissions->exportBatch($claim->payer, [$claim], $this->user);
        $submissions->markSubmitted($batch, 'REF-1', $this->user);

        return $claim->refresh();
    }

    public function test_a_batch_file_is_exported_then_marked_submitted_with_a_reference_and_cannot_be_submitted_twice(): void
    {
        Storage::fake('local');
        $claim = $this->readyClaim();
        $submissions = app(InsuranceSubmissionService::class);

        $batch = $submissions->exportBatch($claim->payer, [$claim], $this->user);
        Storage::disk('local')->assertExists($batch->file_path);
        $this->assertStringContainsString($claim->claim_number, Storage::disk('local')->get($batch->file_path));
        $this->assertSame('ready', $claim->refresh()->status, 'exporting alone does not lock the claim');

        try {
            $submissions->markSubmitted($batch, '', $this->user);
            $this->fail('no reference');
        } catch (ValidationException) {
        }
        $submissions->markSubmitted($batch, 'PORTAL-8841', $this->user);
        $this->assertSame('submitted', $claim->refresh()->status);
        $this->assertSame('PORTAL-8841', $claim->payer_reference);

        $this->expectException(ValidationException::class);
        $submissions->markSubmitted($batch->refresh(), 'AGAIN', $this->user);
    }

    public function test_a_batch_takes_only_ready_claims_of_one_payer(): void
    {
        $a = $this->readyClaim();
        $b = $this->readyClaim();
        $this->expectException(ValidationException::class);
        app(InsuranceSubmissionService::class)->exportBatch($a->payer, [$a, $b], $this->user);
    }

    public function test_the_null_gateway_never_submits_and_the_claim_stays_ready(): void
    {
        $claim = $this->readyClaim();
        $result = app(InsuranceSubmissionService::class)->submitViaGateway($claim->payer, [$claim], $this->user);
        $this->assertSame(['sent' => 0, 'kept' => 1], $result);
        $this->assertSame('ready', $claim->refresh()->status);
    }

    public function test_a_query_is_answered_and_the_claim_returns_to_the_payer(): void
    {
        $claim = $this->submit($this->readyClaim());
        $queries = app(InsuranceQueryService::class);
        $query = $queries->raise($claim, 'query', null, 'Please send the discharge note', $this->user);

        $this->assertSame('queried', $claim->refresh()->status);
        $this->assertNotNull($query->due_at);
        $queries->answer($query, 'Attached', $this->user);
        $this->assertSame('submitted', $claim->refresh()->status);
    }

    public function test_denials_need_a_reason_code_drive_the_worklist_and_can_be_appealed(): void
    {
        $queries = app(InsuranceQueryService::class);
        $first = $this->submit($this->readyClaim());
        $second = $this->submit($this->readyClaim());
        $third = $this->submit($this->readyClaim());

        try {
            $queries->raise($first, 'denial', null, 'No reason', $this->user);
            $this->fail('a denial without a code');
        } catch (ValidationException) {
        }
        $denial = $queries->raise($first, 'denial', 'D03', 'No pre-authorization', $this->user);
        $queries->raise($second, 'denial', 'D03', 'No pre-authorization', $this->user);
        $queries->raise($third, 'rejection', 'D08', 'Missing document', $this->user);

        $worklist = $queries->worklist($this->organization->id);
        $this->assertSame('D03', $worklist->first()['code']);
        $this->assertSame(2, $worklist->first()['count']);
        $this->assertNotEmpty($worklist->first()['fix']);

        $appeal = $queries->appeal($first->refresh(), 'The pre-authorization was granted by phone on the day', $this->user, $denial);
        $this->assertSame('appealed', $first->refresh()->status);
        $queries->decideAppeal($appeal, true, 'Accepted on review', $this->user);
        $this->assertSame('accepted', $first->refresh()->status);
    }

    public function test_a_remittance_is_allocated_posts_a_payment_on_the_invoice_and_settles_the_claim(): void
    {
        $claim = $this->submit($this->readyClaim());
        $remittances = app(InsuranceRemittanceService::class);
        $this->assertSame('2400.00', (string) $claim->payer_share);

        $remittance = $remittances->record($claim->payer, '3000.00', now()->toDateString(), $this->user, 'BANK-5521');
        $remittances->allocate($remittance, $claim, '1000.00', $this->user);

        $this->assertSame('partially_paid', $claim->refresh()->status);
        $this->assertSame('1400.00', $claim->outstanding());
        $invoice = BillingInvoice::find($claim->invoice_id);
        $this->assertSame('1000.00', (string) $invoice->paid_amount);
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('partly_allocated', $remittance->refresh()->status);

        $remittances->allocate($remittance->refresh(), $claim, '1400.00', $this->user);
        $this->assertSame('paid', $claim->refresh()->status);
        $this->assertSame('2400.00', (string) $claim->paid_amount);
        $this->assertSame('600.00', $remittances->patientDue($invoice->refresh()), 'the patient still owes their 20%');
        $this->assertSame('2400.00', number_format((float) $claim->lines()->sum('paid_amount'), 2, '.', ''), 'the payment is spread over the lines');
        $this->assertSame('2400.00', (string) BillingInsurancePolicy::find($claim->policy_id)->annual_used, 'the policy benefits are used');
    }

    public function test_an_allocation_cannot_exceed_the_remittance_the_claim_or_belong_to_another_payer(): void
    {
        $claim = $this->submit($this->readyClaim());
        $other = $this->submit($this->readyClaim());
        $remittances = app(InsuranceRemittanceService::class);
        $remittance = $remittances->record($claim->payer, '500.00', now()->toDateString(), $this->user);

        foreach ([['over the remittance', $claim, '600.00'], ['wrong payer', $other, '100.00'], ['over the claim', $claim, '2500.00']] as [$why, $target, $amount]) {
            try {
                $remittances->allocate($remittance->refresh(), $target, $amount, $this->user);
                $this->fail($why);
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('submitted', $claim->refresh()->status, 'nothing was posted');
        $this->assertSame('0.00', (string) BillingInvoice::find($claim->invoice_id)->paid_amount);
    }

    public function test_a_write_off_needs_a_different_approver_and_closes_what_is_owed(): void
    {
        $claim = $this->submit($this->readyClaim());
        $queries = app(InsuranceQueryService::class);
        $queries->raise($claim, 'denial', 'D07', 'Annual limit reached', $this->user);
        $remittances = app(InsuranceRemittanceService::class);

        $writeoff = $remittances->requestWriteoff($claim->refresh(), '2400.00', 'Payer will not pay; limit reached', $this->user);
        try {
            $remittances->decideWriteoff($writeoff, true, $this->user);
            $this->fail('self-approval');
        } catch (ValidationException) {
        }
        $remittances->decideWriteoff($writeoff, true, $this->staff(['super_admin']));

        $this->assertSame('written_off', $claim->refresh()->status);
        $this->assertSame('0.00', $claim->outstanding());
        $this->expectException(ValidationException::class);
        $remittances->requestWriteoff($claim, '1.00', 'again', $this->user);
    }

    public function test_the_second_policy_is_billed_for_what_the_first_left_with_the_patient(): void
    {
        $patient = $this->patient();
        $primaryPlan = $this->plan(null, ['copay_percent' => 20]);
        $secondaryPlan = $this->plan(null, ['copay_percent' => 0]);
        $first = $this->policy($patient, $primaryPlan);
        $second = $this->policy($patient, $secondaryPlan, ['coverage_order' => 2]);
        $claims = app(InsuranceClaimService::class);
        $primary = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $first), $this->user);
        $primary->documents()->update(['received' => true]);
        $this->assertSame([], array_column($claims->markReady($primary, $this->user), 'rule'));
        $this->submit($primary->refresh());
        $remittances = app(InsuranceRemittanceService::class);
        $remittances->allocate($remittances->record($primary->payer, '800.00', now()->toDateString(), $this->user), $primary->refresh(), '800.00', $this->user);

        $secondary = $claims->generateSecondary($primary->refresh(), $this->user);

        $this->assertSame($secondaryPlan->payer_id, $secondary->payer_id);
        $this->assertSame('200.00', (string) $secondary->gross_amount, 'the 20% the first policy left');
        $this->assertSame('200.00', (string) $secondary->payer_share);
        $this->assertSame($primary->id, $secondary->parent_claim_id);
        $this->assertNotNull($second);

        $this->expectException(ValidationException::class);
        $claims->generateSecondary($primary, $this->user);
    }
}
