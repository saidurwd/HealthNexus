<?php

namespace App\Services\Insurance;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInsuranceProvider;
use App\Models\Insurance\InsurancePayer;
use App\Models\Insurance\InsurancePlan;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A patient's policy with a payer and plan: holder, dependents, validity, member numbers, coverage order. */
class InsurancePolicyService
{
    /** @param  array<string, mixed>  $data  policy_number, member_number, group_number, effective_from, effective_to, relationship_to_subscriber, subscriber_patient_id, coverage_order, coverage_limit */
    public function attach(Patient $holder, InsurancePayer $payer, ?InsurancePlan $plan, array $data): BillingInsurancePolicy
    {
        if ($payer->organization_id !== $holder->organization_id || ($plan && $plan->payer_id !== $payer->id)) {
            throw ValidationException::withMessages(['payer' => 'The payer or plan does not belong here.']);
        }
        if (blank($data['member_number'] ?? null) || blank($data['policy_number'] ?? null) || blank($data['effective_from'] ?? null)) {
            throw ValidationException::withMessages(['policy' => 'A policy needs a policy number, a member number and a start date.']);
        }
        if (isset($data['effective_to']) && $data['effective_to'] < $data['effective_from']) {
            throw ValidationException::withMessages(['effective_to' => 'The end date is before the start date.']);
        }

        // Phase 4's policy table needs an insurer row; any payer without one gets it now.
        $providerId = $payer->insurance_provider_id ?? tap(BillingInsuranceProvider::firstOrCreate(['organization_id' => $payer->organization_id, 'code' => $payer->code], ['name' => $payer->name, 'status' => 'active'])->id, fn ($id) => $payer->update(['insurance_provider_id' => $id]));

        return BillingInsurancePolicy::create($data + [
            'organization_id' => $holder->organization_id, 'insurance_provider_id' => $providerId, 'payer_id' => $payer->id, 'plan_id' => $plan?->id,
            'patient_id' => $holder->id, 'relationship_to_subscriber' => 'self', 'coverage_order' => 1, 'status' => 'active', 'benefit_year_start' => $data['effective_from'],
        ]);
    }

    public function addDependent(BillingInsurancePolicy $policy, Patient $dependent, string $relationship, ?string $memberNumber = null): void
    {
        if ($dependent->organization_id !== $policy->organization_id) {
            throw ValidationException::withMessages(['patient' => 'This person belongs to another organization.']);
        }
        DB::table('insurance_policy_dependents')->updateOrInsert(
            ['policy_id' => $policy->id, 'patient_id' => $dependent->id],
            ['organization_id' => $policy->organization_id, 'relationship' => $relationship, 'member_number' => $memberNumber, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** Policies that cover the patient (as holder or dependent) on a date, primary first. @return Collection<int, BillingInsurancePolicy> */
    public function activeFor(Patient $patient, ?string $date = null): Collection
    {
        $date ??= now()->toDateString();
        $dependentOf = DB::table('insurance_policy_dependents')->where('patient_id', $patient->id)->pluck('policy_id');

        return BillingInsurancePolicy::query()->where('organization_id', $patient->organization_id)->where('status', 'active')
            ->where(fn ($q) => $q->where('patient_id', $patient->id)->orWhereIn('id', $dependentOf))
            ->whereDate('effective_from', '<=', $date)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderBy('coverage_order')->get();
    }

    public function isActiveOn(BillingInsurancePolicy $policy, string $date): bool
    {
        return $policy->status === 'active' && $policy->effective_from->toDateString() <= $date && ($policy->effective_to === null || $policy->effective_to->toDateString() >= $date);
    }
}
