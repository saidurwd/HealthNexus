<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    public function view(User $user, File $file): bool
    {
        return $user->hasRole('super_admin') || $file->uploaded_by === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('file.manage');
    }
}
