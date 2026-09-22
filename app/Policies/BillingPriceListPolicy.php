<?php

namespace App\Policies;

use App\Models\Billing\BillingPriceList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingPriceListPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.pricing.view');
    }

    public function view(User $user, BillingPriceList $priceList): bool
    {
        return $user->can('billing.pricing.view') && $this->inScope($user, $priceList);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.pricing.manage');
    }

    public function update(User $user, BillingPriceList $priceList): bool
    {
        return $user->can('billing.pricing.manage') && $this->inScope($user, $priceList);
    }

    public function delete(User $user, BillingPriceList $priceList): bool
    {
        return $user->can('billing.pricing.manage') && $this->inScope($user, $priceList);
    }

    private function inScope(User $user, BillingPriceList $priceList): bool
    {
        return $user->companies()->where('companies.id', $priceList->company_id)->exists();
    }
}
