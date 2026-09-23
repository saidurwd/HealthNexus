<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyMedication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyMedicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.medication.view');
    }

    public function view(User $user, PharmacyMedication $medication): bool
    {
        return $user->can('pharmacy.medication.view') && $this->inScope($user, $medication);
    }

    public function create(User $user): bool
    {
        return $user->can('pharmacy.medication.create');
    }

    public function update(User $user, PharmacyMedication $medication): bool
    {
        return $user->can('pharmacy.medication.update') && $this->inScope($user, $medication);
    }

    public function delete(User $user, PharmacyMedication $medication): bool
    {
        return $user->can('pharmacy.medication.update') && $this->inScope($user, $medication);
    }

    private function inScope(User $user, PharmacyMedication $medication): bool
    {
        return $user->companies()->where('companies.id', $medication->company_id)->exists()
            && (! $medication->branch_id || $user->branches()->where('branches.id', $medication->branch_id)->exists());
    }
}
