<?php

namespace App\Policies;

use App\Models\Ipd\IpdAdmission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IpdAdmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ipd.admission.view');
    }

    public function view(User $user, IpdAdmission $admission): bool
    {
        return $user->can('ipd.admission.view') && $this->inScope($user, $admission);
    }

    public function create(User $user): bool
    {
        return $user->can('ipd.admission.create');
    }

    public function update(User $user, IpdAdmission $admission): bool
    {
        return $user->can('ipd.admission.update') && $this->inScope($user, $admission);
    }

    private function inScope(User $user, IpdAdmission $admission): bool
    {
        return $user->companies()->where('companies.id', $admission->company_id)->exists()
            && $user->branches()->where('branches.id', $admission->branch_id)->exists();
    }
}
