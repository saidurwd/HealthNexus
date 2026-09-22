<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Patient;
use Illuminate\Support\Carbon;

/**
 * Insurance billing foundation: active-policy resolution and copay application feeding
 * BillingPricingService/BillingCalculationService. No claim submission/adjudication workflow —
 * that is explicitly out of Phase 4 scope; this only produces claim-ready billing records.
 */
class InsuranceBillingService
{
    public function resolveActivePolicy(Patient $patient, ?string $date = null): ?BillingInsurancePolicy
    {
        $date ??= Carbon::now()->toDateString();

        return BillingInsurancePolicy::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->first();
    }

    /**
     * Splits a net amount into the portion the patient owes (copay) and the portion billed to
     * the insurer, per the policy's copay percentage.
     *
     * @return array{patient_amount:string,insurer_amount:string}
     */
    public function applyCopay(BillingInsurancePolicy $policy, string $netAmount): array
    {
        $copayPercent = (string) $policy->copay_percentage;
        $patientAmount = bcdiv(bcmul($netAmount, $copayPercent, 6), '100', 2);
        $insurerAmount = bcsub($netAmount, $patientAmount, 2);

        if ($policy->coverage_limit !== null && bccomp($insurerAmount, (string) $policy->coverage_limit, 2) === 1) {
            $excess = bcsub($insurerAmount, (string) $policy->coverage_limit, 2);
            $insurerAmount = (string) $policy->coverage_limit;
            $patientAmount = bcadd($patientAmount, $excess, 2);
        }

        return [
            'patient_amount' => $patientAmount,
            'insurer_amount' => $insurerAmount,
        ];
    }
}
