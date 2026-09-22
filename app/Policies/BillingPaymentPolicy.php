<?php

namespace App\Policies;

use App\Models\Billing\BillingPayment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingPaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.payment.view');
    }

    public function view(User $user, BillingPayment $payment): bool
    {
        return $user->can('billing.payment.view') && $this->inScope($user, $payment);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.payment.create');
    }

    public function cancel(User $user, BillingPayment $payment): bool
    {
        return $user->can('billing.payment.cancel') && $this->inScope($user, $payment);
    }

    private function inScope(User $user, BillingPayment $payment): bool
    {
        return $user->companies()->where('companies.id', $payment->company_id)->exists()
            && $user->branches()->where('branches.id', $payment->branch_id)->exists();
    }
}
