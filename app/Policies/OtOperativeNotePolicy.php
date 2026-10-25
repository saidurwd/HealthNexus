<?php

namespace App\Policies;

use App\Models\Ot\OtOperativeNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtOperativeNotePolicy
{
    use HandlesAuthorization;

    public function view(User $user, OtOperativeNote $note): bool
    {
        return $user->can('ot.procedure.view') && $this->inScope($user, $note);
    }

    public function update(User $user, OtOperativeNote $note): bool
    {
        return $user->can('ot.procedure.document') && $this->inScope($user, $note);
    }

    public function finalize(User $user, OtOperativeNote $note): bool
    {
        return $user->can('ot.procedure.complete') && $this->inScope($user, $note);
    }

    private function inScope(User $user, OtOperativeNote $note): bool
    {
        $surgery = $note->surgery;

        return $user->companies()->where('companies.id', $surgery->company_id)->exists()
            && (! $surgery->branch_id || $user->branches()->where('branches.id', $surgery->branch_id)->exists());
    }
}
