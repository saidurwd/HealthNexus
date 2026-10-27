<?php

namespace Tests\Feature\Insurance;

use App\Models\Insurance\InsuranceClaim;
use App\Models\Insurance\InsuranceClaimEvent;
use App\Models\Insurance\InsurancePayer;
use App\Models\Patient;
use App\Services\Insurance\InsuranceClaimService;
use App\Services\Insurance\InsuranceEligibilityService;
use App\Services\Insurance\InsuranceMasterService;
use App\Services\Insurance\InsurancePolicyService;
use App\Services\Insurance\InsurancePreauthService;
use Illuminate\Validation\ValidationException;

class InsuranceClaimTest extends InsuranceTestCase
{
    public function test_payers_of_every_kind_share_one_register_and_codes_are_unique(): void
    {
        $service = app(InsuranceMasterService::class);
        foreach (['private_insurer', 'tpa', 'corporate', 'government', 'ngo', 'charity', 'hospital_panel'] as $i => $type) {
            $service->createPayer($this->organization->id, ['code' => "P$i", 'name' => "Payer $i", 'payer_type' => $type]);
        }
        $this->assertSame(7, InsurancePayer::count());
        $this->assertDatabaseHas('billing_insurance_providers', ['code' => 'P0']); // a private insurer also gets the Phase 4 provider row

        $this->expectException(ValidationException::class);
        $service->createPayer($this->organization->id, ['code' => 'P0', 'name' => 'Again', 'payer_type' => 'tpa']);
    }

    public function test_contract_rates_are_effective_dated_and_never_overlap(): void
    {
        $payer = $this->payer();
        $item = $this->invoice($this->patient(), [['XR', 'RAD', '1000.00']])->items()->first();
        $service = app(InsuranceMasterService::class);
        $service->setRate($payer, $item->billing_item_id, '800.00', '2026-01-01');
        $service->setRate($payer, $item->billing_item_id, '700.00', '2026-07-01');

        $this->assertSame('800.00', $service->agreedRate($payer, null, $item->billing_item_id, '2026-03-15'));
        $this->assertSame('700.00', $service->agreedRate($payer, null, $item->billing_item_id, '2026-08-01'));
        $this->assertNull($service->agreedRate($payer, null, $item->billing_item_id, '2025-12-31'));
    }

    public function test_a_policy_covers_the_holder_and_dependents_only_while_valid(): void
    {
        $holder = $this->patient();
        $child = $this->patient();
        $policy = $this->policy($holder);
        $service = app(InsurancePolicyService::class);
        $service->addDependent($policy, $child, 'child', 'MEM-001-2');

        $this->assertCount(1, $service->activeFor($holder));
        $this->assertCount(1, $service->activeFor($child));
        $this->assertCount(0, $service->activeFor($child, now()->addYears(3)->toDateString()));
        $this->assertCount(0, $service->activeFor($this->patient()));

        $this->expectException(ValidationException::class);
        $service->attach($holder, InsurancePayer::find($policy->payer_id), null, ['policy_number' => 'X', 'member_number' => '', 'effective_from' => '2026-01-01']);
    }

    public function test_eligibility_is_recorded_with_who_checked_and_an_expired_policy_cannot_be_eligible(): void
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $service = app(InsuranceEligibilityService::class);

        $check = $service->verifyManually($policy, $patient, $this->user, 'eligible', 'Called the payer');
        $this->assertSame($this->user->id, $check->checked_by);
        $this->assertSame($check->id, $service->latest($policy)->id);

        $adapter = $service->verifyViaAdapter($policy, $patient, $this->user);
        $this->assertSame('unknown', $adapter->status, 'the null adapter never claims eligibility');

        $expired = $this->policy($this->patient(), null, ['effective_from' => now()->subYears(3)->toDateString(), 'effective_to' => now()->subYear()->toDateString()]);
        $this->expectException(ValidationException::class);
        $service->verifyManually($expired, Patient::find($expired->patient_id), $this->user, 'eligible');
    }

    public function test_a_claim_is_built_from_the_invoice_lines_and_the_shares_add_up_to_the_paisa(): void
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $invoice = $this->invoice($patient, [['OPD', 'CONSULT', '1000.00'], ['CBC', 'LAB', '2000.33']], $policy);

        $claim = app(InsuranceClaimService::class)->generate($invoice, $this->user);

        $this->assertSame('draft', $claim->status);
        $this->assertCount(2, $claim->lines);
        $this->assertSame('3000.33', (string) $claim->gross_amount);
        $this->assertSame((string) $claim->gross_amount, bcadd((string) $claim->payer_share, (string) $claim->patient_share, 2));
        $this->assertSame('2400.26', (string) $claim->payer_share, '20% co-pay: 600.07 patient, 2400.26 payer');
        $this->assertSame($claim->lines->sum('invoice_item_id') > 0, true, 'lines tie to invoice lines');
        $this->assertSame('J06.9', $claim->diagnoses[0]['code']);
        $this->assertNotNull($claim->filing_deadline);
        $this->assertStringStartsWith('CLM-', $claim->claim_number);

        try {
            app(InsuranceClaimService::class)->generate($invoice, $this->user);
            $this->fail('second claim for the same invoice and payer');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_the_scrub_lists_what_is_wrong_with_hints_and_ready_needs_a_clean_scrub(): void
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $policy, false), $this->user);

        $errors = $claims->markReady($claim, $this->user);
        $rules = array_column($errors, 'rule');

        $this->assertContains('diagnosis_present', $rules);
        $this->assertContains('documents_complete', $rules);
        $this->assertNotEmpty($errors[0]['hint']);
        $this->assertSame('draft', $claim->refresh()->status);
        $this->assertCount(count($errors), $claim->scrub_errors);

        // fix: add the documents; the claim is still missing the diagnosis, so it is still not ready
        $claim->documents()->update(['received' => true]);
        $this->assertContains('diagnosis_present', array_column($claims->markReady($claim->refresh(), $this->user), 'rule'));

        $claim->update(['diagnoses' => [['code' => 'J06.9', 'system' => 'ICD-10', 'description' => 'URTI']]]);
        $this->assertSame([], $claims->markReady($claim->refresh(), $this->user));
        $this->assertSame('ready', $claim->refresh()->status);
    }

    public function test_illegal_transitions_are_rejected_and_a_submitted_claim_is_locked(): void
    {
        $patient = $this->patient();
        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $this->policy($patient)), $this->user);

        foreach (['paid', 'submitted', 'accepted', 'closed'] as $illegal) {
            try {
                $claims->transition($claim, $illegal, $this->user);
                $this->fail("draft → $illegal accepted");
            } catch (ValidationException) {
            }
        }
        $claim->documents()->update(['received' => true]);
        $claims->markReady($claim, $this->user);
        $claims->transition($claim->refresh(), 'submitted', $this->user);

        $this->assertTrue($claim->refresh()->isLocked());
        $this->assertNotNull($claim->submitted_at);
        $this->expectException(ValidationException::class);
        $claims->transition($claim, 'draft', $this->user);
    }

    public function test_a_rejected_claim_is_corrected_as_a_new_version_and_the_old_one_stays(): void
    {
        $patient = $this->patient();
        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, [['OPD', 'CONSULT', '1000.00']], $this->policy($patient)), $this->user);
        $claim->documents()->update(['received' => true]);
        $claims->markReady($claim, $this->user);
        $claims->transition($claim->refresh(), 'submitted', $this->user);
        $claims->transition($claim->refresh(), 'rejected', $this->user, 'Wrong member number');

        $new = $claims->newVersion($claim->refresh(), $this->user);

        $this->assertSame(2, $new->version);
        $this->assertSame($claim->id, $new->parent_claim_id);
        $this->assertStringEndsWith('-V2', $new->claim_number);
        $this->assertSame('cancelled', $claim->refresh()->status);
        $this->assertSame('rejected', $claim->events()->where('to_status', 'rejected')->first()->to_status, 'the history is kept');
        $this->assertSame(2, InsuranceClaim::where('invoice_id', $claim->invoice_id)->count());
    }

    public function test_the_claim_timeline_is_append_only(): void
    {
        $patient = $this->patient();
        $claim = app(InsuranceClaimService::class)->generate($this->invoice($patient, [['OPD', 'CONSULT', '500.00']], $this->policy($patient)), $this->user);
        $this->expectException(\LogicException::class);
        InsuranceClaimEvent::where('claim_id', $claim->id)->firstOrFail()->update(['note' => 'edited']);
    }

    public function test_preauthorization_caps_the_payer_and_is_consumed_then_released_on_cancel(): void
    {
        $patient = $this->patient();
        $plan = $this->plan(null, ['copay_percent' => 0]);
        $plan->rules()->create(['organization_id' => $plan->organization_id, 'rule_type' => 'cover', 'match_type' => 'category', 'match_value' => 'SURGERY', 'requires_preauth' => true]);
        $policy = $this->policy($patient, $plan);
        $preauths = app(InsurancePreauthService::class);
        $request = $preauths->request($policy, $patient, $this->user, 'Appendicectomy', '5000.00');
        $preauths->decide($request, 'partial', '3000.00', 'AUTH-77');

        $claims = app(InsuranceClaimService::class);
        $claim = $claims->generate($this->invoice($patient, [['APPX', 'SURGERY', '5000.00']], $policy), $this->user);

        $this->assertSame('3000.00', (string) $claim->payer_share, 'capped at what was authorised');
        $this->assertSame('2000.00', (string) $claim->patient_share);
        $this->assertSame('3000.00', (string) $request->refresh()->consumed_amount);
        $this->assertNull($preauths->usable($policy, now()->toDateString()), 'nothing left to use');

        $claims->transition($claim, 'cancelled', $this->user);
        $this->assertSame('0.00', (string) $request->refresh()->consumed_amount, 'released when the claim is cancelled');
    }

    public function test_a_preauth_decision_must_fit_and_cannot_be_decided_twice_or_overspent(): void
    {
        $patient = $this->patient();
        $policy = $this->policy($patient);
        $preauths = app(InsurancePreauthService::class);
        $request = $preauths->request($policy, $patient, $this->user, 'MRI', '4000.00');

        try {
            $preauths->decide($request, 'approved', '1000.00', null);
            $this->fail('approved for less than asked');
        } catch (ValidationException) {
        }
        $approved = $preauths->decide($request, 'approved', '4000.00', 'A-1');
        try {
            $preauths->decide($approved, 'rejected', '0', null);
            $this->fail('decided twice');
        } catch (ValidationException) {
        }
        $preauths->consume($approved, '3500.00');
        $this->expectException(ValidationException::class);
        $preauths->consume($approved->refresh(), '600.00');
    }
}
