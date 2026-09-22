<?php

namespace App\Policies;

use App\Models\Billing\BillingAdjustment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingAdjustmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.adjustment.request') || $user->can('billing.adjustment.approve');
    }

    public function view(User $user, BillingAdjustment $adjustment): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $adjustment);
    }

    public function request(User $user): bool
    {
        return $user->can('billing.adjustment.request');
    }

    public function approve(User $user, BillingAdjustment $adjustment): bool
    {
        return $user->can('billing.adjustment.approve') && $this->inScope($user, $adjustment);
    }

    private function inScope(User $user, BillingAdjustment $adjustment): bool
    {
        return $user->companies()->where('companies.id', $adjustment->company_id)->exists()
            && $user->branches()->where('branches.id', $adjustment->branch_id)->exists();
    }
}
