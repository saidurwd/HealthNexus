<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->companies()->where('companies.id', $patient->company_id)->exists();
    }

    public function merge(User $user): bool
    {
        return $user->can('patients.update') || $user->hasRole('super_admin');
    }

    public function uploadDocument(User $user, Patient $patient): bool
    {
        return $this->update($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->can('patients.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete') || $user->hasRole('super_admin');
    }
}
