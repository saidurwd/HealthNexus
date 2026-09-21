<?php

namespace App\Policies;

use App\Models\Country;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Country $country): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function update(User $user, Country $country): bool
    {
        return $user->hasPermissionTo('settings.update');
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->hasRole('super_admin');
    }
}
