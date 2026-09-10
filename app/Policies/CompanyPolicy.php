<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->companies()->where('companies.id', $company->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create companies') || $user->hasRole('super_admin');
    }

    public function update(User $user, Company $company): bool
    {
        $access = $user->companies()->where('companies.id', $company->id)->first()?->pivot?->access_level;

        return in_array($access, ['owner', 'admin']) || $user->hasRole('super_admin');
    }

    public function delete(User $user, Company $company): bool
    {
        $access = $user->companies()->where('companies.id', $company->id)->first()?->pivot?->access_level;

        return $access === 'owner' || $user->hasRole('super_admin');
    }
}
