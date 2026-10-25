<?php

namespace App\Services\Ot;

use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalTeam;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Append-only team assignment history (spec §17) — mirrors IpdProviderAssignmentService exactly:
 * reassigning a role ends the prior active row rather than overwriting it.
 */
class OtSurgicalTeamService
{
    public function assign(OtSurgery $surgery, Provider $provider, string $role, User $user, ?string $reason = null): OtSurgicalTeam
    {
        return DB::transaction(function () use ($surgery, $provider, $role, $user, $reason) {
            OtSurgicalTeam::query()
                ->where('surgery_id', $surgery->id)
                ->where('role', $role)
                ->whereNull('removed_at')
                ->update(['removed_at' => now(), 'reason' => $reason]);

            $assignment = OtSurgicalTeam::create([
                'surgery_id' => $surgery->id,
                'provider_id' => $provider->id,
                'role' => $role,
                'assigned_at' => now(),
                'assigned_by' => $user->id,
            ]);

            match ($role) {
                OtSurgicalTeam::ROLE_PRIMARY_SURGEON => $surgery->update(['primary_surgeon_id' => $provider->id]),
                OtSurgicalTeam::ROLE_ASSISTANT_SURGEON => $surgery->update(['assistant_surgeon_id' => $provider->id]),
                OtSurgicalTeam::ROLE_ANESTHETIST => $surgery->update(['anesthetist_id' => $provider->id]),
                default => null,
            };

            return $assignment;
        });
    }

    public function remove(OtSurgicalTeam $assignment, ?string $reason = null): OtSurgicalTeam
    {
        $assignment->update(['removed_at' => now(), 'reason' => $reason]);

        return $assignment->refresh();
    }
}
