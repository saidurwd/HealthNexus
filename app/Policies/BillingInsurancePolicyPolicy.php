<?php

namespace App\Policies;

use App\Models\Billing\BillingInsurancePolicy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Named with the doubled "Policy" suffix (Laravel's {Model}Policy convention applied to a model
 * that is itself already called "...Policy") rather than a misleading shortened name.
 */
class BillingInsurancePolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.insurance.policy.view');
    }

    public function view(User $user, BillingInsurancePolicy $policy): bool
    {
        return $user->can('billing.insurance.policy.view') && $this->inScope($user, $policy);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.insurance.policy.manage');
    }

    public function update(User $user, BillingInsurancePolicy $policy): bool
    {
        return $user->can('billing.insurance.policy.manage') && $this->inScope($user, $policy);
    }

    private function inScope(User $user, BillingInsurancePolicy $policy): bool
    {
        return $user->companies()->where('companies.id', $policy->company_id)->exists();
    }
}
