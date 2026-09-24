<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdProviderAssignment;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Append-only provider assignment history (spec §21/§73): reassigning a role ends the prior
 * active row rather than overwriting it. IpdAdmission.admitting_provider_id/attending_provider_id
 * remain denormalized "current" pointers for fast queries, refreshed here alongside the history.
 */
class IpdProviderAssignmentService
{
    public function assign(IpdAdmission $admission, Provider $provider, string $role, User $user): IpdProviderAssignment
    {
        return DB::transaction(function () use ($admission, $provider, $role, $user) {
            IpdProviderAssignment::query()
                ->where('admission_id', $admission->id)
                ->where('role', $role)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $assignment = IpdProviderAssignment::create([
                'admission_id' => $admission->id,
                'provider_id' => $provider->id,
                'role' => $role,
                'assigned_at' => now(),
                'assigned_by' => $user->id,
            ]);

            if ($role === IpdProviderAssignment::ROLE_ADMITTING) {
                $admission->update(['admitting_provider_id' => $provider->id]);
            } elseif ($role === IpdProviderAssignment::ROLE_ATTENDING) {
                $admission->update(['attending_provider_id' => $provider->id]);
            }

            return $assignment;
        });
    }

    public function currentFor(IpdAdmission $admission, string $role): ?IpdProviderAssignment
    {
        return IpdProviderAssignment::query()
            ->where('admission_id', $admission->id)
            ->where('role', $role)
            ->whereNull('ended_at')
            ->latest('assigned_at')
            ->first();
    }
}
