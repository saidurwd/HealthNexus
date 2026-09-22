<?php

namespace App\Policies;

use App\Models\Billing\BillingCharge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingChargePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.charge.view');
    }

    public function view(User $user, BillingCharge $charge): bool
    {
        return $user->can('billing.charge.view') && $this->inScope($user, $charge);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.charge.create');
    }

    public function cancel(User $user, BillingCharge $charge): bool
    {
        return $user->can('billing.charge.cancel') && $this->inScope($user, $charge);
    }

    private function inScope(User $user, BillingCharge $charge): bool
    {
        return $user->companies()->where('companies.id', $charge->company_id)->exists()
            && $user->branches()->where('branches.id', $charge->branch_id)->exists();
    }
}
