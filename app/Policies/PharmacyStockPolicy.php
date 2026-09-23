<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyStore;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyStockPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.stock.view');
    }

    public function view(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.stock.view') && $this->inScope($user, $store);
    }

    public function receive(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.stock.receive') && $this->inScope($user, $store);
    }

    public function adjust(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.stock.adjust') && $this->inScope($user, $store);
    }

    public function count(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.stock.count') && $this->inScope($user, $store);
    }

    public function viewControlledDrugs(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.controlled_drug.view') && $this->inScope($user, $store);
    }

    public function manageControlledDrugs(User $user, PharmacyStore $store): bool
    {
        return $user->can('pharmacy.controlled_drug.manage') && $this->inScope($user, $store);
    }

    private function inScope(User $user, PharmacyStore $store): bool
    {
        return $user->companies()->where('companies.id', $store->company_id)->exists()
            && (! $store->branch_id || $user->branches()->where('branches.id', $store->branch_id)->exists());
    }
}
