<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiagnosisPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Diagnosis $diagnosis): bool
    {
        return $user->companies()->where('companies.id', $diagnosis->company_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('diagnoses.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('diagnoses.update') || $user->hasRole('super_admin');
    }

    public function delete(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('diagnoses.delete') || $user->hasRole('super_admin');
    }
}
