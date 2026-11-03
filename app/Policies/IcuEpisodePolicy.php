<?php

namespace App\Policies;

use App\Models\Icu\IcuEpisode;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IcuEpisodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('icu.episode.view');
    }

    public function view(User $user, IcuEpisode $episode): bool
    {
        return $user->can('icu.episode.view') && $this->inScope($user, $episode);
    }

    public function create(User $user): bool
    {
        return $user->can('icu.episode.create');
    }

    public function update(User $user, IcuEpisode $episode): bool
    {
        return $user->can('icu.episode.update') && $this->inScope($user, $episode);
    }

    public function admit(User $user, IcuEpisode $episode): bool
    {
        return $user->can('icu.episode.admit') && $this->inScope($user, $episode);
    }

    public function transfer(User $user, IcuEpisode $episode): bool
    {
        return $user->can('icu.episode.transfer') && $this->inScope($user, $episode);
    }

    public function discharge(User $user, IcuEpisode $episode): bool
    {
        return $user->can('icu.episode.discharge') && $this->inScope($user, $episode);
    }

    private function inScope(User $user, IcuEpisode $episode): bool
    {
        return $user->organizations()->where('organizations.id', $episode->organization_id)->exists()
            && (! $episode->branch_id || $user->branches()->where('branches.id', $episode->branch_id)->exists());
    }
}
