<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyProcedurePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.procedure.view');
    }

    public function view(User $user, RadiologyProcedure $procedure): bool
    {
        return $user->can('radiology.procedure.view') && $this->inScope($user, $procedure);
    }

    public function create(User $user): bool
    {
        return $user->can('radiology.procedure.create');
    }

    public function update(User $user, RadiologyProcedure $procedure): bool
    {
        return $user->can('radiology.procedure.update') && $this->inScope($user, $procedure);
    }

    public function delete(User $user, RadiologyProcedure $procedure): bool
    {
        return $user->can('radiology.procedure.update') && $this->inScope($user, $procedure);
    }

    private function inScope(User $user, RadiologyProcedure $procedure): bool
    {
        return $user->companies()->where('companies.id', $procedure->company_id)->exists();
    }
}
