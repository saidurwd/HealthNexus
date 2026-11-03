<?php

namespace App\Services\Interop;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;

/** Staff services want "the user who did it". Records that arrive from outside are made as one locked, login-less service user per hospital; the partner and message are in the audit trail. */
class InteropSystemUser
{
    public function for(int $organizationId): User
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $email = 'integration-service@'.Str::slug($organization->code ?: $organization->slug ?: (string) $organization->id).'.system.invalid';
        $user = User::query()->where('email', $email)->first() ?? User::query()->create(['name' => 'Integration (service)', 'email' => $email, 'password' => Str::random(48), 'is_active' => false]);
        if (! $user->organizations()->where('organizations.id', $organizationId)->exists()) {
            $user->organizations()->attach($organizationId, ['access_level' => 'staff', 'is_default' => true]);
        }

        return $user;
    }
}
