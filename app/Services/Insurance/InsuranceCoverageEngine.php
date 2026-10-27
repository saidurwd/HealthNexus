<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsurancePlanRule;
use Illuminate\Support\Collection;

/**
 * The split of a bill between the payer and the patient. Pure arithmetic over DECIMAL strings; it prices nothing (Billing owns prices — override 1).
 *
 * Per line, in order: the agreed contract rate caps the amount the payer will consider; an exclusion takes the whole line out; a limit rule
 * caps what is covered; the deductible comes off next; the co-pay (a rule's own percent, or the plan's) is taken from what is left; finally the
 * visit and annual limits cap what the payer can pay. The patient owes the rest — always, so that
 *     payer share + patient share + contractual adjustment (gross − contract amount) = gross, to the paisa.
 * Rounding remainders land on the patient's share.
 */
class InsuranceCoverageEngine
{
    /**
     * @param  list<array{key: string|int, item_code?: ?string, category?: ?string, item_type?: ?string, description?: string, quantity?: int, gross: string, agreed_unit_rate?: ?string}>  $lines
     * @param  array{deductible_remaining?: string, annual_remaining?: ?string, visit_remaining?: ?string, preauth_remaining?: ?string}  $state  what the policy still has available
     * @return array{lines: array<int|string, array<string, string>>, totals: array<string, string>}
     */
    public function split(InsurancePlan $plan, array $lines, array $state = []): array
    {
        $rules = $plan->rules()->get();
        $deductible = $state['deductible_remaining'] ?? $this->nonNegative(bcsub((string) $plan->deductible_amount, '0', 2));
        $annual = $state['annual_remaining'] ?? ($plan->annual_limit !== null ? (string) $plan->annual_limit : null);
        $visit = $state['visit_remaining'] ?? ($plan->per_visit_limit !== null ? (string) $plan->per_visit_limit : null);
        $preauth = $state['preauth_remaining'] ?? null;
        $ruleUsed = [];
        $out = [];
        $totals = array_fill_keys(['gross', 'contract', 'adjustment', 'non_covered', 'deductible', 'copay', 'payer', 'patient'], '0.00');

        foreach ($lines as $line) {
            $gross = $this->money($line['gross']);
            $contract = $gross;
            if (isset($line['agreed_unit_rate']) && $line['agreed_unit_rate'] !== null) {
                $agreed = bcmul($this->money($line['agreed_unit_rate']), (string) ($line['quantity'] ?? 1), 2);
                $contract = bccomp($agreed, $gross, 2) < 0 ? $agreed : $gross;
            }

            $note = [];
            $nonCovered = '0.00';
            $copayPercent = (string) $plan->copay_percent;
            $needsPreauth = false;

            $exclude = $this->match($rules, 'exclude', $line);
            $limit = $this->match($rules, 'limit', $line);
            $cover = $this->match($rules, 'cover', $line);

            $covered = $contract;
            if ($exclude) {
                $nonCovered = $contract;
                $covered = '0.00';
                $note[] = 'excluded'.($exclude->note ? ": {$exclude->note}" : '');
            } else {
                if ($limit && $limit->limit_amount !== null) {
                    $left = bcsub((string) $limit->limit_amount, $ruleUsed[$limit->id] ?? '0.00', 2);
                    $left = $this->nonNegative($left);
                    if (bccomp($covered, $left, 2) === 1) {
                        $nonCovered = bcsub($covered, $left, 2);
                        $covered = $left;
                        $note[] = 'capped by a limit rule';
                    }
                    $ruleUsed[$limit->id] = bcadd($ruleUsed[$limit->id] ?? '0.00', $covered, 2);
                }
                if ($cover && $cover->copay_percent !== null) {
                    $copayPercent = (string) $cover->copay_percent;
                    $note[] = "co-pay {$copayPercent}% by rule";
                }
                $needsPreauth = (bool) ($cover?->requires_preauth || $limit?->requires_preauth);
            }

            $ded = bccomp($covered, $deductible, 2) === -1 ? $covered : $deductible;
            $deductible = bcsub($deductible, $ded, 2);
            $afterDeductible = bcsub($covered, $ded, 2);
            $ded !== '0.00' && $note[] = 'deductible';

            $copay = $this->round(bcdiv(bcmul($afterDeductible, $copayPercent, 6), '100', 6));
            $payer = bcsub($afterDeductible, $copay, 2);

            if ($visit !== null && bccomp($payer, $visit, 2) === 1) {
                $payer = $this->nonNegative($visit);
                $note[] = 'visit limit reached';
            }
            if ($annual !== null && bccomp($payer, $annual, 2) === 1) {
                $payer = $this->nonNegative($annual);
                $note[] = 'annual limit reached';
            }
            if ($needsPreauth && $preauth !== null && bccomp($payer, $preauth, 2) === 1) {
                $payer = $this->nonNegative($preauth);
                $note[] = 'capped by the pre-authorization';
            }

            $visit !== null && $visit = bcsub($visit, $payer, 2);
            $annual !== null && $annual = bcsub($annual, $payer, 2);
            $preauth !== null && $needsPreauth && $preauth = bcsub($preauth, $payer, 2);

            $patient = bcsub($contract, $payer, 2);
            $adjustment = bcsub($gross, $contract, 2);
            $out[$line['key']] = ['gross' => $gross, 'contract' => $contract, 'adjustment' => $adjustment, 'non_covered' => $nonCovered, 'deductible' => $ded, 'copay' => $copay, 'payer' => $payer, 'patient' => $patient, 'note' => implode('; ', $note)];
            foreach (['gross', 'contract', 'adjustment', 'non_covered', 'payer', 'patient'] as $k) {
                $totals[$k] = bcadd($totals[$k], $out[$line['key']][$k], 2);
            }
            $totals['deductible'] = bcadd($totals['deductible'], $ded, 2);
            $totals['copay'] = bcadd($totals['copay'], $copay, 2);
        }

        return ['lines' => $out, 'totals' => $totals];
    }

    /** The policy's remaining allowances, to feed `split()`. @return array{deductible_remaining: string, annual_remaining: ?string} */
    public function state(BillingInsurancePolicy $policy, InsurancePlan $plan): array
    {
        $deductible = $this->nonNegative(bcsub((string) $plan->deductible_amount, (string) $policy->deductible_met, 2));
        $limit = $policy->coverage_limit ?? $plan->annual_limit;

        return ['deductible_remaining' => $deductible, 'annual_remaining' => $limit !== null ? $this->nonNegative(bcsub((string) $limit, (string) $policy->annual_used, 2)) : null];
    }

    /** @param  Collection<int, InsurancePlanRule>  $rules */
    private function match($rules, string $type, array $line): ?InsurancePlanRule
    {
        $candidates = $rules->where('rule_type', $type);
        foreach ([['item_code', $line['item_code'] ?? null], ['category', $line['category'] ?? null], ['item_type', $line['item_type'] ?? null]] as [$matchType, $value]) {
            if ($value === null) {
                continue;
            }
            $hit = $candidates->first(fn ($r) => $r->match_type === $matchType && strcasecmp($r->match_value, (string) $value) === 0);
            if ($hit) {
                return $hit;
            }
        }

        return null;
    }

    private function money(string|int|float $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function nonNegative(string $value): string
    {
        return bccomp($value, '0', 2) < 0 ? '0.00' : $value;
    }

    /** Half-up to 2 places on a 6-place string. */
    private function round(string $value): string
    {
        return $this->halfUp($value);
    }

    private function halfUp(string $value): string
    {
        $cents = bcadd(bcmul($value, '100', 6), '0.5', 6);
        $whole = explode('.', $cents)[0];

        return bcdiv($whole, '100', 2);
    }
}
