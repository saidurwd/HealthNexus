<?php

namespace App\Policies;

use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PharmacyDispensingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('pharmacy.dispensing.view');
    }

    public function view(User $user, PharmacyDispensing $dispensing): bool
    {
        return $user->can('pharmacy.dispensing.view') && $this->inScope($user, $dispensing);
    }

    public function create(User $user): bool
    {
        return $user->can('pharmacy.dispensing.create');
    }

    public function verify(User $user, PharmacyDispensing $dispensing): bool
    {
        return $user->can('pharmacy.dispensing.verify') && $this->inScope($user, $dispensing);
    }

    public function cancel(User $user, PharmacyDispensing $dispensing): bool
    {
        return $user->can('pharmacy.dispensing.cancel') && $this->inScope($user, $dispensing);
    }

    public function return(User $user, PharmacyDispensing $dispensing): bool
    {
        return $user->can('pharmacy.dispensing.return') && $this->inScope($user, $dispensing);
    }

    private function inScope(User $user, PharmacyDispensing $dispensing): bool
    {
        return $user->companies()->where('companies.id', $dispensing->company_id)->exists()
            && $user->branches()->where('branches.id', $dispensing->branch_id)->exists();
    }
}
