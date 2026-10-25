<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FALaravel\Facades\Google2FA;

class Google2faService
{
    public function generateSecretKey(User $user): string
    {
        return Google2FA::generateSecretKey();
    }

    public function getQRCodeUrl(User $user, string $secret): string
    {
        $organization = $user->organizations()->first();
        $organizationName = $organization ? $organization->name : config('app.name');

        return Google2FA::getQRCodeUrl($organizationName, $user->email, $secret);
    }

    public function verifySecret(User $user, string $secret, string $code): bool
    {
        $valid = Google2FA::verifyKey($secret, $code);

        if (! $valid) {
            return false;
        }

        return true;
    }

    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->google2fa_secret);

        return $this->verifySecret($user, $secret, $code);
    }

    public function generateRecoveryCodes(int $count = 10, int $length = 16): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes($length / 2)));
        }

        return $codes;
    }
}
