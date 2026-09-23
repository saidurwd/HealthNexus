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
        return $user->can('patients.merge') || $user->hasRole('super_admin');
    }

    public function uploadDocument(User $user, Patient $patient): bool
    {
        return ($user->can('patients.documents.manage') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    public function viewDocuments(User $user, Patient $patient): bool
    {
        return ($user->can('patients.documents.view') && $this->inScope($user, $patient)) || $this->uploadDocument($user, $patient);
    }

    public function manageConsents(User $user, Patient $patient): bool
    {
        return ($user->can('patients.consents.manage') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    public function requestAmendment(User $user, Patient $patient): bool
    {
        return ($user->can('patients.amend.request') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    public function approveAmendment(User $user): bool
    {
        return $user->can('patients.amend.approve') || $user->hasRole('super_admin');
    }

    public function print(User $user, Patient $patient): bool
    {
        return ($user->can('patients.print') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    public function create(User $user): bool
    {
        return $user->can('patients.create') || $user->hasRole('super_admin');
    }

    public function update(User $user, Patient $patient): bool
    {
        return ($user->can('patients.update') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return ($user->can('patients.delete') && $this->inScope($user, $patient)) || $user->hasRole('super_admin');
    }

    private function inScope(User $user, Patient $patient): bool
    {
        return $user->companies()->where('companies.id', $patient->company_id)->exists();
    }
}
