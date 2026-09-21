<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrescriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->companies()->where('companies.id', $prescription->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('prescriptions.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.delete') || $user->hasRole('super_admin');
    }
}
