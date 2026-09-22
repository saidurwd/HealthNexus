<?php

namespace App\Policies;

use App\Models\Billing\BillingRefund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingRefundPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.refund.request') || $user->can('billing.refund.approve') || $user->can('billing.refund.process');
    }

    public function view(User $user, BillingRefund $refund): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $refund);
    }

    public function request(User $user): bool
    {
        return $user->can('billing.refund.request');
    }

    public function approve(User $user, BillingRefund $refund): bool
    {
        return $user->can('billing.refund.approve') && $this->inScope($user, $refund);
    }

    public function process(User $user, BillingRefund $refund): bool
    {
        return $user->can('billing.refund.process') && $this->inScope($user, $refund);
    }

    private function inScope(User $user, BillingRefund $refund): bool
    {
        return $user->companies()->where('companies.id', $refund->company_id)->exists()
            && $user->branches()->where('branches.id', $refund->branch_id)->exists();
    }
}
