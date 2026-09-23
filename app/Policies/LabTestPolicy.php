<?php

namespace App\Policies;

use App\Models\Laboratory\LabTest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabTestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.test.view');
    }

    public function view(User $user, LabTest $test): bool
    {
        return $user->can('lab.test.view') && $this->inScope($user, $test);
    }

    public function create(User $user): bool
    {
        return $user->can('lab.test.create');
    }

    public function update(User $user, LabTest $test): bool
    {
        return $user->can('lab.test.update') && $this->inScope($user, $test);
    }

    public function delete(User $user, LabTest $test): bool
    {
        return $user->can('lab.test.update') && $this->inScope($user, $test);
    }

    private function inScope(User $user, LabTest $test): bool
    {
        return $user->companies()->where('companies.id', $test->company_id)->exists();
    }
}
