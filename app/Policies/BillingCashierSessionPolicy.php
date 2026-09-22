<?php

namespace App\Policies;

use App\Models\Billing\BillingCashierSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingCashierSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.cashier.view');
    }

    public function view(User $user, BillingCashierSession $session): bool
    {
        return ($user->can('billing.cashier.view') && $this->inScope($user, $session))
            || $session->user_id === $user->id;
    }

    public function open(User $user): bool
    {
        return $user->can('billing.cashier.open');
    }

    public function close(User $user, BillingCashierSession $session): bool
    {
        return $user->can('billing.cashier.close') && $session->user_id === $user->id;
    }

    public function reconcile(User $user, BillingCashierSession $session): bool
    {
        return $user->can('billing.cashier.reconcile') && $this->inScope($user, $session);
    }

    private function inScope(User $user, BillingCashierSession $session): bool
    {
        return $user->companies()->where('companies.id', $session->company_id)->exists()
            && $user->branches()->where('branches.id', $session->branch_id)->exists();
    }
}
