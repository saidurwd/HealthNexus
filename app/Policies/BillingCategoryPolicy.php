<?php

namespace App\Policies;

use App\Models\Billing\BillingCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('billing.pricing.view');
    }

    public function view(User $user, BillingCategory $category): bool
    {
        return $user->can('billing.pricing.view') && $this->inScope($user, $category);
    }

    public function create(User $user): bool
    {
        return $user->can('billing.category.manage');
    }

    public function update(User $user, BillingCategory $category): bool
    {
        return $user->can('billing.category.manage') && $this->inScope($user, $category);
    }

    public function delete(User $user, BillingCategory $category): bool
    {
        return $user->can('billing.category.manage') && $this->inScope($user, $category);
    }

    private function inScope(User $user, BillingCategory $category): bool
    {
        return $user->companies()->where('companies.id', $category->company_id)->exists();
    }
}
