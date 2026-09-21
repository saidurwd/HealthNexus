<?php

namespace App\Http\Controllers\Auth\Mfa;

use App\Http\Controllers\Controller;
use App\Services\Google2faService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function __construct(private Google2faService $google2fa) {}

    public function showChallenge(Request $request)
    {
        $user = $request->user();

        return view('auth.mfa.challenge', [
            'user' => $user,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $code = $request->input('one_time_password');

        if (! $this->google2fa->verify($user, $code)) {
            throw ValidationException::withMessages([
                'one_time_password' => ['The provided MFA code is incorrect.'],
            ]);
        }

        $user->update([
            'mfa_last_used_at' => now(),
        ]);

        $request->session()->put('mfa_verified', true);

        return response()->json([
            'success' => true,
            'message' => 'MFA verified successfully.',
            'redirect' => route('home'),
        ]);
    }

    public function showSetup(Request $request)
    {
        $user = $request->user();
        $secret = $this->google2fa->generateSecretKey($user);

        return view('auth.mfa.setup', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $this->google2fa->getQRCodeUrl($user, $secret),
        ]);
    }

    public function setup(Request $request): JsonResponse
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'size:6'],
            'secret' => ['required', 'string'],
        ]);

        $user = $request->user();
        $code = $request->input('one_time_password');
        $secret = $request->input('secret');

        if (! $this->google2fa->verifySecret($user, $secret, $code)) {
            throw ValidationException::withMessages([
                'one_time_password' => ['The provided MFA code is incorrect.'],
            ]);
        }

        $recoveryCodes = $this->google2fa->generateRecoveryCodes();

        $user->update([
            'google2fa_secret' => encrypt($secret),
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
            'mfa_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MFA enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $code = $request->input('one_time_password');

        if (! $this->google2fa->verify($user, $code)) {
            throw ValidationException::withMessages([
                'one_time_password' => ['The provided MFA code is incorrect.'],
            ]);
        }

        $user->update([
            'google2fa_secret' => null,
            'mfa_enabled' => false,
            'mfa_confirmed_at' => null,
            'mfa_recovery_codes' => null,
            'mfa_last_used_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MFA disabled successfully.',
        ]);
    }
}
