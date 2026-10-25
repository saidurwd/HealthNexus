<?php

namespace App\Policies;

use App\Models\Icu\IcuProgressNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuProgressNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.note.view');
    }

    public function view(User $user, IcuProgressNote $note): bool
    {
        return $user->can('icu.note.view') && $this->inScope($user, $note);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.note.create');
    }

    public function update(User $user, IcuProgressNote $note): bool
    {
        return $user->can('icu.note.update') && $this->inScope($user, $note);
    }

    public function finalize(User $user, IcuProgressNote $note): bool
    {
        return $user->can('icu.note.finalize') && $this->inScope($user, $note);
    }

    private function inScope(User $user, IcuProgressNote $note): bool
    {
        return $user->companies()->where('companies.id', $note->company_id)->exists()
            && (! $note->branch_id || $user->branches()->where('branches.id', $note->branch_id)->exists());
    }
}
