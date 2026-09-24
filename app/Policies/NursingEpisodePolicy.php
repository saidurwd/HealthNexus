<?php

namespace App\Policies;

use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NursingEpisodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('nursing.dashboard.view');
    }

    public function view(User $user, NursingEpisode $episode): bool
    {
        return $user->can('nursing.dashboard.view') && $this->inScope($user, $episode) && $this->assignedOrPrivileged($user, $episode);
    }

    public function update(User $user, NursingEpisode $episode): bool
    {
        return $user->can('nursing.assignment.update') && $this->inScope($user, $episode) && $this->assignedOrPrivileged($user, $episode);
    }

    /**
     * Company/branch tenant boundary (mandatory: Hospital-A-nurse-cannot-touch-Hospital-B-patient).
     */
    public function inScope(User $user, NursingEpisode $episode): bool
    {
        return $user->companies()->where('companies.id', $episode->company_id)->exists()
            && (! $episode->branch_id || $user->branches()->where('branches.id', $episode->branch_id)->exists());
    }

    /**
     * Ward-level isolation (mandatory: Ward-A-nurse-cannot-touch-Ward-B-patient, spec §75/§91) —
     * a supervisory role sees every episode in scope; a staff nurse must hold an active
     * nursing_assignments row for this episode.
     */
    public function assignedOrPrivileged(User $user, NursingEpisode $episode): bool
    {
        if ($user->hasRole(['hospital_admin', 'charge_nurse', 'nursing_supervisor', 'nursing_administrator', 'doctor'])) {
            return true;
        }

        return $episode->assignments()->whereNull('ended_at')->where('nurse_id', $user->id)->exists();
    }
}
