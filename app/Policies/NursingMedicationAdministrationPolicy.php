<?php

namespace App\Policies;

use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingMedicationAdministrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.mar.view');
    }

    public function view(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.view') && $this->inScope($user, $mar);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.mar.administer');
    }

    public function administer(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.administer') && $this->inScope($user, $mar);
    }

    public function hold(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.hold') && $this->inScope($user, $mar);
    }

    public function refuse(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.refuse') && $this->inScope($user, $mar);
    }

    public function omit(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.omit') && $this->inScope($user, $mar);
    }

    public function correct(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->can('nursing.mar.correct') && $this->inScope($user, $mar);
    }

    private function inScope(User $user, NursingMedicationAdministration $mar): bool
    {
        return $user->companies()->where('companies.id', $mar->company_id)->exists()
            && (! $mar->branch_id || $user->branches()->where('branches.id', $mar->branch_id)->exists());
    }
}
