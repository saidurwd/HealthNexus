<?php

namespace App\Services\Insurance;

use App\Contracts\Integration\PayerGateway;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Encounter;
use App\Models\Insurance\InsuranceEligibilityCheck;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Is this person covered today? Checked by hand (call, portal, card) or through the PayerGateway; the visit shows the latest answer and who gave it. */
class InsuranceEligibilityService
{
    public function __construct(private readonly PayerGateway $gateway, private readonly InsurancePolicyService $policies) {}

    public function verifyManually(BillingInsurancePolicy $policy, Patient $patient, User $by, string $status, ?string $note = null, ?Encounter $encounter = null): InsuranceEligibilityCheck
    {
        if (! in_array($status, ['eligible', 'not_eligible', 'unknown'], true)) {
            throw ValidationException::withMessages(['status' => 'Not a known answer.']);
        }
        // Whatever the payer says, an expired or stopped policy is never "eligible" on the service date.
        if ($status === 'eligible' && ! $this->policies->isActiveOn($policy, now()->toDateString())) {
            throw ValidationException::withMessages(['status' => 'The policy is not active today, so it cannot be recorded as eligible.']);
        }

        return $this->record($policy, $patient, $by, 'manual', $status, $note, null, $encounter);
    }

    /** Asks the payer's interface. With no real adapter (the null one) the answer is recorded as unknown, never as eligible. */
    public function verifyViaAdapter(BillingInsurancePolicy $policy, Patient $patient, User $by, ?Encounter $encounter = null): InsuranceEligibilityCheck
    {
        $result = $this->gateway->checkEligibility(['policy_number' => $policy->policy_number, 'member_number' => $policy->member_number, 'patient_id' => $patient->id, 'payer_id' => $policy->payer_id]);
        $status = $result->handled ? (($result->data['eligible'] ?? false) ? 'eligible' : 'not_eligible') : 'unknown';

        return $this->record($policy, $patient, $by, 'adapter', $status, $result->handled ? null : $result->reason, $result->data, $encounter);
    }

    public function latest(BillingInsurancePolicy $policy, ?Encounter $encounter = null): ?InsuranceEligibilityCheck
    {
        return InsuranceEligibilityCheck::query()->where('policy_id', $policy->id)->when($encounter, fn ($q) => $q->where('encounter_id', $encounter->id))->latest('checked_at')->latest('id')->first();
    }

    /** @param  array<string, mixed>|null  $response */
    private function record(BillingInsurancePolicy $policy, Patient $patient, User $by, string $method, string $status, ?string $note, ?array $response, ?Encounter $encounter): InsuranceEligibilityCheck
    {
        return InsuranceEligibilityCheck::create([
            'organization_id' => $policy->organization_id, 'policy_id' => $policy->id, 'patient_id' => $patient->id, 'encounter_id' => $encounter?->id,
            'method' => $method, 'status' => $status, 'note' => $note, 'response' => $response, 'checked_at' => now(), 'checked_by' => $by->id,
        ]);
    }
}
