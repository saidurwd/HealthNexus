<?php

namespace App\Policies;

use App\Models\Billing\BillingItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.pricing.view');
    }

    public function view(User $user, BillingItem $item): bool
    {
        return $user->can('billing.pricing.view') && $this->inScope($user, $item);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.item.manage');
    }

    public function update(User $user, BillingItem $item): bool
    {
        return $user->can('billing.item.manage') && $this->inScope($user, $item);
    }

    public function delete(User $user, BillingItem $item): bool
    {
        return $user->can('billing.item.manage') && $this->inScope($user, $item);
    }

    private function inScope(User $user, BillingItem $item): bool
    {
        return $user->companies()->where('companies.id', $item->company_id)->exists();
    }
}
