<?php

namespace App\Policies;

use App\Models\Nursing\NursingNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.notes.view');
    }

    public function view(User $user, NursingNote $note): bool
    {
        return $user->can('nursing.notes.view') && $this->inScope($user, $note);
    }

    public function create(User $user): bool
    {
        return $user->can('nursing.notes.create');
    }

    public function update(User $user, NursingNote $note): bool
    {
        return $user->can('nursing.notes.create') && $this->inScope($user, $note);
    }

    public function finalize(User $user, NursingNote $note): bool
    {
        return $user->can('nursing.notes.finalize') && $this->inScope($user, $note);
    }

    public function amend(User $user, NursingNote $note): bool
    {
        return $user->can('nursing.notes.amend') && $this->inScope($user, $note);
    }

    private function inScope(User $user, NursingNote $note): bool
    {
        return $user->companies()->where('companies.id', $note->company_id)->exists()
            && (! $note->branch_id || $user->branches()->where('branches.id', $note->branch_id)->exists());
    }
}
