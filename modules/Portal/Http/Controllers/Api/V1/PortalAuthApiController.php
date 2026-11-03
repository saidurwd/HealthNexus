<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Portal\PortalDevice;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Registering, signing in and managing the phone's session (S01, S02). The same answers, whether or not a person exists. */
class PortalAuthApiController extends Controller
{
    public function __construct(private readonly PortalAuthService $auth) {}

    private function out(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'meta' => []], $status);
    }

    private function device(Request $request): array
    {
        $d = $request->validate(['device.uid' => ['required', 'string', 'max:100'], 'device.name' => ['nullable', 'string', 'max:100'], 'device.platform' => ['nullable', 'in:web,android,ios'], 'device.push_token' => ['nullable', 'string', 'max:500']]);

        return $d['device'];
    }

    public function config(): JsonResponse
    {
        return $this->out(['api_version' => config('portal.api.version'), 'min_app_version' => config('portal.api.min_app_version'), 'languages' => ['bn', 'en'], 'emergency_number' => config('portal.messaging.emergency_number'),
            'payments_enabled' => app(PortalPaymentService::class)->available()]);
    }

    public function registerStart(Request $request): JsonResponse
    {
        $d = $request->validate(['hospital' => ['nullable', 'string', 'max:60'], 'identifier' => ['required', 'string', 'max:60'], 'date_of_birth' => ['required', 'date'], 'phone' => ['required', 'string', 'max:25']]);

        return $this->out($this->auth->startRegistration($d), __('If the details match our records, a code has been sent to the phone number we have for you.'));
    }

    public function registerComplete(Request $request): JsonResponse
    {
        $d = $request->validate(['challenge' => ['required', 'string', 'max:60'], 'code' => ['required', 'string', 'max:10'], 'password' => ['required', 'string', 'max:100'], 'accept_terms' => ['required', 'boolean']]);
        $device = $this->device($request);
        $account = $this->auth->completeRegistration($d['challenge'], $d['code'], $d['password'], (bool) $d['accept_terms']);

        return $this->session($account, $device, 201);
    }

    public function claimInvitation(Request $request): JsonResponse
    {
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'date_of_birth' => ['required', 'date'], 'password' => ['required', 'string', 'max:100'], 'accept_terms' => ['required', 'boolean']]);
        $device = $this->device($request);

        return $this->session($this->auth->claimInvitation($d['code'], $d['date_of_birth'], $d['password'], (bool) $d['accept_terms']), $device, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $d = $request->validate(['phone' => ['required', 'string', 'max:25'], 'password' => ['required', 'string', 'max:100']]);
        $device = $this->device($request);
        $r = $this->auth->attempt($d['phone'], $d['password'], $device['uid'], $device['name'] ?? '', $device['platform'] ?? 'android');
        if ($r['otp_required']) {
            return $this->out(['otp_required' => true, 'challenge' => $r['challenge']], __('We sent a code to your phone.'));
        }

        return $this->session($r['account'], $device);
    }

    public function loginOtp(Request $request): JsonResponse
    {
        $d = $request->validate(['challenge' => ['required', 'string', 'max:60'], 'code' => ['required', 'string', 'max:10']]);
        $device = $this->device($request);

        return $this->session($this->auth->completeLoginOtp($d['challenge'], $d['code']), $device);
    }

    public function forgot(Request $request): JsonResponse
    {
        $d = $request->validate(['phone' => ['required', 'string', 'max:25']]);

        return $this->out($this->auth->startReset($d['phone']), __('If this number has a login, a code has been sent to it.'));
    }

    public function reset(Request $request): JsonResponse
    {
        $d = $request->validate(['challenge' => ['required', 'string', 'max:60'], 'code' => ['required', 'string', 'max:10'], 'password' => ['required', 'string', 'max:100']]);
        $this->auth->completeReset($d['challenge'], $d['code'], $d['password']);

        return $this->out(null, __('Your password was changed. Please sign in again.'));
    }

    public function logout(Request $request): JsonResponse
    {
        $account = $request->attributes->get('portal_account');
        $token = $account->currentAccessToken();
        $device = PortalDevice::query()->where('account_id', $account->id)->where('token_id', $token?->id)->first();
        $device ? $this->auth->revokeDevice($account, $device, 'signed out') : $token?->delete();

        return $this->out(null, __('Signed out.'));
    }

    public function devices(Request $request): JsonResponse
    {
        $account = $request->attributes->get('portal_account');
        $current = $account->currentAccessToken()?->id;

        return $this->out($account->devices()->whereNull('revoked_at')->orderByDesc('last_seen_at')->get()->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'platform' => $d->platform, 'last_seen_at' => $d->last_seen_at?->toIso8601String(), 'current' => $d->token_id === $current])->all());
    }

    public function revokeDevice(Request $request, int $device): JsonResponse
    {
        $account = $request->attributes->get('portal_account');
        $this->auth->revokeDevice($account, PortalDevice::query()->where('account_id', $account->id)->findOrFail($device));

        return $this->out(null, __('That device was signed out.'));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $d = $request->validate(['current' => ['required', 'string'], 'password' => ['required', 'string', 'max:100']]);
        $this->auth->changePassword($request->attributes->get('portal_account'), $d['current'], $d['password']);

        return $this->out(null, __('Your password was changed.'));
    }

    public function pushToken(Request $request): JsonResponse
    {
        $d = $request->validate(['push_token' => ['required', 'string', 'max:500']]);
        $account = $request->attributes->get('portal_account');
        PortalDevice::query()->where('account_id', $account->id)->where('token_id', $account->currentAccessToken()?->id)->first()?->update(['push_token' => $d['push_token']]);

        return $this->out(null, __('Saved.'));
    }

    private function session($account, array $device, int $status = 200): JsonResponse
    {
        [$row, $token] = $this->auth->registerDevice($account, $device['uid'], $device['name'] ?? '', $device['platform'] ?? 'android', true, $device['push_token'] ?? null);

        return $this->out(['token' => $token, 'token_type' => 'Bearer', 'expires_in_days' => (int) config('portal.tokens.ttl_days'), 'language' => $account->language, 'name' => trim($account->patient->first_name.' '.$account->patient->last_name)], __('Signed in.'), $status);
    }
}
