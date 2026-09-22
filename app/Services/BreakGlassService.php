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
    public function requestAccess(Encounter $encounter, string $reason, ?User $user = null, ?Request $request = null, ?string $scope = null): BreakGlassAccess
    {
        $request ??= request();
        $user ??= Auth::user();

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
