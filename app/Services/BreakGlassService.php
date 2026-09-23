<?php

namespace App\Services;

use App\Models\BreakGlassAccess;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakGlassService
{
    /**
     * Break-glass is emergency access to a record the requester is normally denied — within
     * their own hospital (e.g. a restricted VIP or a patient outside their usual department),
     * not a cross-hospital bypass. Without this check, any holder of clinical.break_glass could
     * self-grant write access to any encounter ID in any company.
     */
    public function requestAccess(Encounter $encounter, string $reason, ?User $user = null, ?Request $request = null, ?string $scope = null): BreakGlassAccess
    {
        $request ??= request();
        $user ??= Auth::user();

        abort_unless(
            $user->companies()->where('companies.id', $encounter->company_id)->exists(),
            403,
            'Break-glass access is only available within your own organization.'
        );

        return BreakGlassAccess::create([
            'user_id' => $user->id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'scope' => $scope,
            'expires_at' => now()->addHour(),
        ]);
    }

    public function hasActiveAccess(User $user, ?Encounter $encounter = null): bool
    {
        $query = BreakGlassAccess::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->whereNull('revoked_at');

        if ($encounter) {
            $query->where('encounter_id', $encounter->id);
        }

        return $query->exists();
    }

    public function revoke(User $user, ?Encounter $encounter = null): void
    {
        BreakGlassAccess::where('user_id', $user->id)
            ->when($encounter, fn ($q) => $q->where('encounter_id', $encounter->id))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update([
                'revoked_at' => now(),
                'revoked_by' => Auth::id(),
            ]);
    }
}
