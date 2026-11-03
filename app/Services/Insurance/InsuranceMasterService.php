<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingInsuranceProvider;
use App\Models\Insurance\InsuranceContractRate;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePlanRule;
use Illuminate\Validation\ValidationException;

/** Payers (every kind, one register), their plans, coverage rules and effective-dated contract rates. */
class InsuranceMasterService
{
    public function __construct(private readonly InsurancePolicySettings $settings) {}

    /** @param  array<string, mixed>  $data */
    public function createPayer(int $organizationId, array $data): InsurancePayer
    {
        if (! in_array($data['payer_type'] ?? null, config('insurance.payer_types'), true)) {
            throw ValidationException::withMessages(['payer_type' => 'Not a known kind of payer.']);
        }
        if (InsurancePayer::query()->where('organization_id', $organizationId)->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'A payer with this code already exists.']);
        }
        // A private insurer also gets the Phase 4 provider row, so Billing's existing policy screens keep working.
        $data['insurance_provider_id'] ??= in_array($data['payer_type'], ['private_insurer', 'tpa'], true)
            ? BillingInsuranceProvider::firstOrCreate(['organization_id' => $organizationId, 'code' => $data['code']], ['name' => $data['name'], 'status' => 'active'])->id
            : null;

        return InsurancePayer::create($data + ['organization_id' => $organizationId]);
    }

    /** @param  array<string, mixed>  $data */
    public function createPlan(InsurancePayer $payer, array $data): InsurancePlan
    {
        foreach (['copay_percent'] as $pct) {
            if (isset($data[$pct]) && ($data[$pct] < 0 || $data[$pct] > 100)) {
                throw ValidationException::withMessages([$pct => 'A percentage must be between 0 and 100.']);
            }
        }

        return InsurancePlan::create($data + ['organization_id' => $payer->organization_id, 'payer_id' => $payer->id]);
    }

    public function addRule(InsurancePlan $plan, string $type, string $matchType, string $matchValue, array $options = []): InsurancePlanRule
    {
        if (! in_array($type, ['exclude', 'cover', 'limit'], true) || ! in_array($matchType, ['item_code', 'category', 'item_type'], true)) {
            throw ValidationException::withMessages(['rule' => 'Not a known kind of rule.']);
        }
        if ($type === 'limit' && ! isset($options['limit_amount'])) {
            throw ValidationException::withMessages(['limit_amount' => 'A limit rule needs an amount.']);
        }

        return InsurancePlanRule::create($options + ['organization_id' => $plan->organization_id, 'plan_id' => $plan->id, 'rule_type' => $type, 'match_type' => $matchType, 'match_value' => $matchValue]);
    }

    public function setRate(InsurancePayer $payer, int $billingItemId, string $rate, string $from, ?string $to = null, ?InsurancePlan $plan = null): InsuranceContractRate
    {
        if (bccomp($rate, '0', 2) < 0) {
            throw ValidationException::withMessages(['agreed_rate' => 'A rate cannot be negative.']);
        }
        // A new rate closes the one it replaces the day before it starts, so rates never overlap.
        InsuranceContractRate::query()->where('payer_id', $payer->id)->where('billing_item_id', $billingItemId)->where('plan_id', $plan?->id)
            ->whereNull('effective_to')->whereDate('effective_from', '<', $from)->update(['effective_to' => date('Y-m-d', strtotime("$from -1 day"))]);

        return InsuranceContractRate::create([
            'organization_id' => $payer->organization_id, 'payer_id' => $payer->id, 'plan_id' => $plan?->id, 'billing_item_id' => $billingItemId,
            'agreed_rate' => $rate, 'effective_from' => $from, 'effective_to' => $to,
        ]);
    }

    /** The agreed unit rate on a date: the plan's own rate wins over the payer-wide one. */
    public function agreedRate(InsurancePayer $payer, ?InsurancePlan $plan, int $billingItemId, string $date): ?string
    {
        $rates = InsuranceContractRate::query()->where('payer_id', $payer->id)->where('billing_item_id', $billingItemId)
            ->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))->get();
        $rate = $rates->firstWhere('plan_id', $plan?->id) ?? $rates->firstWhere('plan_id', null);

        return $rate ? (string) $rate->agreed_rate : null;
    }

    public function filingDays(InsurancePayer $payer): int
    {
        return (int) ($payer->filing_deadline_days ?: $this->settings->get('filing_deadline_days'));
    }
}
