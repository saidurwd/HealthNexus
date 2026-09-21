<?php

namespace App\Policies;

use App\Models\InvestigationOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvestigationOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InvestigationOrder $order): bool
    {
        return $user->companies()->where('companies.id', $order->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('investigations.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, InvestigationOrder $order): bool
    {
        return $user->can('investigations.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, InvestigationOrder $order): bool
    {
        return $user->can('investigations.delete') || $user->hasRole('super_admin');
    }
}
