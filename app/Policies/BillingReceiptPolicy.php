<?php

namespace App\Policies;

use App\Models\Billing\BillingReceipt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingReceiptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.receipt.view');
    }

    public function view(User $user, BillingReceipt $receipt): bool
    {
        return $user->can('billing.receipt.view') && $this->inScope($user, $receipt);
    }

    public function void(User $user, BillingReceipt $receipt): bool
    {
        return $user->can('billing.receipt.void') && $this->inScope($user, $receipt);
    }

    private function inScope(User $user, BillingReceipt $receipt): bool
    {
        return $user->organizations()->where('organizations.id', $receipt->organization_id)->exists()
            && $user->branches()->where('branches.id', $receipt->branch_id)->exists();
    }
}
