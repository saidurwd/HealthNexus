<?php

namespace App\Policies;

use App\Models\Billing\BillingCorporate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingCorporatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.corporate.view');
    }

    public function view(User $user, BillingCorporate $corporate): bool
    {
        return $user->can('billing.corporate.view') && $this->inScope($user, $corporate);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.corporate.manage');
    }

    public function update(User $user, BillingCorporate $corporate): bool
    {
        return $user->can('billing.corporate.manage') && $this->inScope($user, $corporate);
    }

    private function inScope(User $user, BillingCorporate $corporate): bool
    {
        return $user->companies()->where('companies.id', $corporate->company_id)->exists();
    }
}
