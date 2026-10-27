<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingItem;
use App\Models\Patient;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Insurance\InsuranceMasterService;
use App\Services\Insurance\InsurancePolicyService;

/**
 * Insurance, first half: payers of every kind with plans, coverage rules and contract rates, then policies for a third of the patients
 * (some with dependents, a few with a second policy) and corporate accounts with members. Runs BEFORE billing, so invoices can carry the
 * policy and the insurance hook drafts the claims. Policies and rates are valid from the start of the history.
 */
class InsuranceSetupHistory implements DemoHistoryModule
{
    private const PAYERS = [
        ['PRIME', 'Prime Life Insurance', 'private_insurer', [['PRIME-GOLD', 'Gold', 10, 0, 500000], ['PRIME-BASIC', 'Basic', 20, 500, 200000]]],
        ['SAFE', 'SafeHealth Insurance', 'private_insurer', [['SAFE-STD', 'Standard', 20, 1000, 300000]]],
        ['CARE', 'CareFirst TPA', 'tpa', [['CARE-PLUS', 'Plus', 15, 0, 250000]]],
        ['GOVT', 'National Health Scheme', 'government', [['GOVT-BPL', 'Below poverty line', 0, 0, 100000]]],
        ['HOPE', 'Hope Charity Fund', 'charity', [['HOPE-AID', 'Patient aid', 0, 0, 50000]]],
        ['PANEL', 'City Bank staff panel', 'hospital_panel', [['PANEL-STAFF', 'Staff', 10, 0, 150000]]],
    ];

    public function __construct(private readonly InsuranceMasterService $masters, private readonly InsurancePolicyService $policies) {}

    public function name(): string
    {
        return 'Insurance — payers and policies';
    }

    public function module(): string
    {
        return 'insurance';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $start = $context->from->subDay();
        $plans = [];
        foreach (self::PAYERS as [$code, $name, $type, $planRows]) {
            $payer = $this->masters->createPayer($orgId, ['code' => $code, 'name' => $name, 'payer_type' => $type, 'submission_method' => $type === 'government' ? 'portal_upload' : 'file_export', 'filing_deadline_days' => 90, 'payment_terms_days' => 30]);
            foreach ($planRows as [$planCode, $planName, $copay, $deductible, $limit]) {
                $plan = $this->masters->createPlan($payer, ['code' => $planCode, 'name' => $planName, 'copay_percent' => $copay, 'deductible_amount' => $deductible, 'annual_limit' => $limit]);
                $this->masters->addRule($plan, 'limit', 'category', 'LAB', ['limit_amount' => 25000]);
                $this->masters->addRule($plan, 'cover', 'category', 'PROC', ['requires_preauth' => true]);
                if ($type === 'private_insurer') {
                    $this->masters->addRule($plan, 'exclude', 'category', 'COSMETIC', ['note' => 'Cosmetic services are not covered']);
                }
                $plans[] = $plan;
            }
            // contract rates: 90% of the list price for diagnostics, from before the history starts
            if (in_array($type, ['private_insurer', 'tpa'], true)) {
                BillingItem::query()->where('organization_id', $orgId)->whereIn('item_type', ['lab_test', 'radiology'])->each(fn (BillingItem $item) => $this->masters->setRate($payer, $item->id, number_format((float) $item->base_price * 0.9, 2, '.', ''), $start->toDateString()));
            }
        }

        // corporate accounts (billed on monthly statements, not through claims)
        $corporates = [];
        foreach ([['ACME', 'Acme Garments Ltd'], ['BDTEL', 'BD Telecom Ltd']] as [$code, $name]) {
            $corporates[] = BillingCorporate::create(['organization_id' => $orgId, 'name' => $name, 'code' => $code, 'credit_limit' => '5000000.00', 'payment_terms_days' => 30, 'billing_cycle' => 'monthly', 'status' => 'active']);
        }

        $patients = $context->patients()->shuffle();
        $insured = [];
        $counts = ['policies' => 0, 'dependents' => 0, 'second' => 0, 'corporate' => 0];
        foreach ($patients->take((int) round($patients->count() * 0.33)) as $i => $patient) {
            $plan = $context->pick($plans);
            $policy = $this->policies->attach($patient, $plan->payer, $plan, [
                'policy_number' => sprintf('POL-%06d', 100000 + $i), 'member_number' => sprintf('MEM-%06d', 500000 + $i), 'effective_from' => $start->subYear()->toDateString(), 'effective_to' => $context->to->addYear()->toDateString(),
            ]);
            $insured[$patient->id] = ['policy' => $policy->id];
            $counts['policies']++;

            if ($context->chance(20) && ($dependent = $patients->first(fn (Patient $p) => ! isset($insured[$p->id]) && $p->id !== $patient->id))) {
                $this->policies->addDependent($policy, $dependent, $context->pick(['spouse', 'child']), $policy->member_number.'-D');
                $insured[$dependent->id] = ['policy' => $policy->id];
                $counts['dependents']++;
            }
            if ($context->chance(10)) {
                $second = $context->pick(array_filter($plans, fn ($p) => $p->payer_id !== $plan->payer_id));
                $this->policies->attach($patient, $second->payer, $second, ['policy_number' => sprintf('POL-%06d', 700000 + $i), 'member_number' => sprintf('MEM-%06d', 900000 + $i), 'effective_from' => $start->subYear()->toDateString(), 'coverage_order' => 2]);
                $counts['second']++;
            }
        }
        foreach ($patients->filter(fn ($p) => ! isset($insured[$p->id]))->take(max(3, (int) round($patients->count() * 0.08))) as $i => $patient) {
            $corporate = $corporates[$i % count($corporates)];
            $insured[$patient->id] = ['corporate' => $corporate->id];
            $counts['corporate']++;
        }

        $context->share('insured', $insured);

        return sprintf('%d payers, %d plans, %d policies (%d dependents, %d second policies), %d corporate members', count(self::PAYERS), count($plans), $counts['policies'], $counts['dependents'], $counts['second'], $counts['corporate']);
    }
}
