<?php

namespace App\Services\Portal;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalDevice;
use App\Models\Portal\PortalSecurityEvent;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creating and using a patient login (S01, S02). Three rules shape every method here:
 *  1. Nothing says whether a person exists. Registration and password reset give the same answer and take the same steps for a match and a miss.
 *  2. The one-time code goes to the phone number ON THE PATIENT RECORD, never to a number the caller typed.
 *  3. Too many wrong tries lock the phone number (not only the account), so guessing is as slow for a stranger as for the owner.
 */
class PortalAuthService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly PortalPolicy $policy,
        private readonly PortalAudit $audit,
        private readonly AuditLogger $auditLog,
    ) {}

    /**
     * @param  array{hospital?:string|null,identifier:string,date_of_birth:string,phone:string}  $in
     * @return array{challenge:string} always the same shape, whether or not a patient matched
     */
    public function startRegistration(array $in): array
    {
        $challenge = Str::random(40);
        $phone = PhoneNumber::normalize($in['phone']);
        $this->throttleStart('register_start', PhoneNumber::hash($phone));
        $patient = $phone ? $this->match($in, $phone) : null;

        if ($patient === null) {
            $this->audit->security('register_fail', null, null, PhoneNumber::hash($phone));
            hash('sha256', $challenge); // same work either way

            return ['challenge' => $challenge];
        }

        $account = DB::transaction(function () use ($patient, $phone, $challenge) {
            $account = PatientPortalAccount::query()->where('patient_id', $patient->id)->lockForUpdate()->first();
            if ($account && $account->status === 'active') {
                return null; // already registered: the owner is told by text, the caller learns nothing
            }
            $account ??= new PatientPortalAccount(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id]);
            $account->fill(['phone' => $phone, 'status' => 'pending', 'is_active' => false, 'email' => $account->email ?: $patient->email, 'language' => $account->language ?: 'bn',
                'invitation_hash' => hash('sha256', $challenge), 'invitation_expires_at' => now()->addMinutes($this->policy->int('otp.ttl_minutes', 10) + 20), 'invited_at' => $account->invited_at ?: now()])->save();

            return $account;
        });

        if ($account === null) {
            return ['challenge' => $challenge];
        }

        $this->otp->issue($patient->organization_id, $phone, 'register', $account, $patient->id, $account->language);

        return ['challenge' => $challenge];
    }

    public function completeRegistration(string $challenge, string $code, string $password, bool $acceptTerms): PatientPortalAccount
    {
        $this->assertPassword($password);
        $account = PatientPortalAccount::query()->where('invitation_hash', hash('sha256', $challenge))->where('status', 'pending')->where('invitation_expires_at', '>', now())->first();
        $phone = $account?->phone;

        if ($account === null || ! $phone || ! $this->otp->verify($phone, 'register', $code)) {
            $this->audit->security('register_fail', $account?->organization_id, $account, PhoneNumber::hash($phone));
            throw new PortalException(__('The code or details are not right. Please check and try again.'));
        }
        if (! $acceptTerms) {
            throw new PortalException(__('Please accept the terms of use to continue.'));
        }

        return $this->activate($account, $password);
    }

    /** Staff-assisted route: the front desk checked the person's identity and hands over a code. Returns [account, plain code]. */
    public function invite(Patient $patient, ?int $userId = null): array
    {
        $plain = strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        $account = PatientPortalAccount::query()->where('patient_id', $patient->id)->first() ?? new PatientPortalAccount(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id]);
        if ($account->exists && $account->status === 'active') {
            throw new PortalException(__('This patient already has a portal login.'), 409);
        }
        $account->fill(['status' => 'invited', 'is_active' => false, 'phone' => PhoneNumber::normalize($patient->phone), 'email' => $account->email ?: $patient->email, 'invited_at' => now(),
            'invitation_hash' => hash('sha256', $plain), 'invitation_expires_at' => now()->addDays($this->policy->int('registration.invitation_ttl_days', 14))])->save();
        $this->auditLog->log('CREATE', PatientPortalAccount::class, $account->id, null, ['patient_id' => $patient->id, 'by' => $userId, 'kind' => 'invitation']);

        return [$account, $plain];
    }

    public function claimInvitation(string $code, string $dateOfBirth, string $password, bool $acceptTerms): PatientPortalAccount
    {
        $this->assertPassword($password);
        $account = PatientPortalAccount::query()->where('invitation_hash', hash('sha256', strtoupper(trim($code))))->where('status', 'invited')->where('invitation_expires_at', '>', now())->with('patient')->first();
        if ($account === null || ! $account->patient?->date_of_birth || $account->patient->date_of_birth->toDateString() !== $this->date($dateOfBirth)) {
            $this->audit->security('register_fail', $account?->organization_id, $account, null, ['via' => 'invitation']);
            throw new PortalException(__('The code or details are not right. Please check and try again.'));
        }
        if (! $acceptTerms) {
            throw new PortalException(__('Please accept the terms of use to continue.'));
        }

        return $this->activate($account, $password);
    }

    private function activate(PatientPortalAccount $account, string $password): PatientPortalAccount
    {
        $account->forceFill(['password' => $password, 'status' => 'active', 'is_active' => true, 'activated_at' => now(), 'invitation_hash' => null, 'invitation_expires_at' => null,
            'failed_attempts' => 0, 'locked_until' => null, 'terms_accepted_at' => now(), 'terms_version' => (string) $this->policy->get('terms_version', '1')])->save();
        $account->patient()->update(['portal_enabled' => true]);
        $this->auditLog->log('UPDATE', PatientPortalAccount::class, $account->id, null, ['status' => 'active']);

        return $account;
    }

    /**
     * Password check. Returns the account, or says "needs a code" when the device is new. Failures are counted on the phone number.
     *
     * @return array{account:?PatientPortalAccount,otp_required:bool,challenge:?string}
     */
    public function attempt(string $phoneInput, string $password, string $deviceUid, string $deviceName = '', string $platform = 'web'): array
    {
        $phone = PhoneNumber::normalize($phoneInput);
        $phoneHash = PhoneNumber::hash($phone);
        $window = $this->policy->int('login.lock_minutes', 15);
        $max = $this->policy->int('login.max_failures', 5);

        if ($phoneHash !== null && $this->audit->countRecent('login_fail', 'phone_hash', $phoneHash, $window) >= $max) {
            $this->audit->security('locked', null, null, $phoneHash);
            PatientPortalAccount::query()->where('phone', $phone)->where('status', 'active')->update(['locked_until' => now()->addMinutes($window)]);
            throw new PortalException(__('Too many wrong tries. Please wait :minutes minutes or reset your password.', ['minutes' => $window]), 429);
        }

        $found = null;
        foreach (PatientPortalAccount::query()->where('phone', $phone)->whereIn('status', ['active', 'locked'])->limit(5)->get() as $candidate) {
            if ($candidate->password && Hash::check($password, $candidate->password)) {
                $found = $candidate;
                break;
            }
        }
        if ($found === null) {
            Hash::check($password, self::dummyHash()); // a miss costs the same time as a wrong password, so timing does not say which numbers have a login
        }

        if ($found === null || ! $found->canLogin()) {
            $this->audit->security('login_fail', $found?->organization_id, $found, $phoneHash);
            if ($found?->isLocked()) {
                throw new PortalException(__('This login is locked. Please wait or reset your password.'), 429);
            }
            throw new PortalException(__('The phone number or password is not right.'), 422);
        }

        $trusted = PortalDevice::query()->where(['account_id' => $found->id, 'device_hash' => $this->deviceHash($deviceUid)])->whereNotNull('trusted_at')->whereNull('revoked_at')->exists();
        if ($found->mfa_required || (! $trusted && $this->policy->get('login.new_device_needs_otp', true))) {
            $challenge = Str::random(40);
            $found->forceFill(['invitation_hash' => hash('sha256', $challenge), 'invitation_expires_at' => now()->addMinutes($this->policy->int('otp.ttl_minutes', 10) + 20)])->save();
            $this->otp->issue($found->organization_id, (string) $found->phone, 'login', $found, $found->patient_id, $found->language);
            $this->audit->security('new_device', $found->organization_id, $found, $phoneHash);

            return ['account' => null, 'otp_required' => true, 'challenge' => $challenge];
        }

        return ['account' => $this->signedIn($found), 'otp_required' => false, 'challenge' => null];
    }

    public function completeLoginOtp(string $challenge, string $code): PatientPortalAccount
    {
        $account = PatientPortalAccount::query()->where('invitation_hash', hash('sha256', $challenge))->where('status', 'active')->where('invitation_expires_at', '>', now())->first();
        if ($account === null || ! $this->otp->verify((string) $account->phone, 'login', $code)) {
            $this->audit->security('login_fail', $account?->organization_id, $account, PhoneNumber::hash($account?->phone), ['step' => 'otp']);
            throw new PortalException(__('The code is not right or has expired.'));
        }
        $account->forceFill(['invitation_hash' => null, 'invitation_expires_at' => null])->save();

        return $this->signedIn($account);
    }

    private function signedIn(PatientPortalAccount $account): PatientPortalAccount
    {
        $account->forceFill(['failed_attempts' => 0, 'locked_until' => null, 'last_login_at' => now(), 'last_login_ip' => request()->ip()])->save();
        $this->audit->security('login_ok', $account->organization_id, $account, PhoneNumber::hash($account->phone));

        return $account;
    }

    /** Remember (trust) the device and, for the mobile app, hand out a token that carries only the ability `portal`. */
    public function registerDevice(PatientPortalAccount $account, string $deviceUid, string $name, string $platform, bool $withToken, ?string $pushToken = null): array
    {
        $device = PortalDevice::query()->updateOrCreate(
            ['account_id' => $account->id, 'device_hash' => $this->deviceHash($deviceUid)],
            ['name' => mb_substr($name, 0, 100) ?: $platform, 'platform' => in_array($platform, ['web', 'android', 'ios'], true) ? $platform : 'web', 'trusted_at' => now(), 'last_seen_at' => now(), 'last_ip' => request()->ip(), 'revoked_at' => null, 'revoked_reason' => null]
                + ($pushToken !== null ? ['push_token' => $pushToken] : []),
        );
        $plain = null;
        if ($withToken) {
            if ($device->token_id) {
                $account->tokens()->where('id', $device->token_id)->delete();
            }
            $token = $account->createToken('portal:'.$device->id, [(string) $this->policy->get('tokens.ability', 'portal')], CarbonImmutable::now()->addDays($this->policy->int('tokens.ttl_days', 30)));
            $device->update(['token_id' => $token->accessToken->id]);
            $plain = $token->plainTextToken;
        }

        return [$device, $plain];
    }

    public function revokeDevice(PatientPortalAccount $account, PortalDevice $device, string $reason = 'revoked by the patient'): void
    {
        abort_unless($device->account_id === $account->id, 404);
        if ($device->token_id) {
            $account->tokens()->where('id', $device->token_id)->delete();
        }
        $device->update(['revoked_at' => now(), 'revoked_reason' => $reason, 'trusted_at' => null, 'token_id' => null, 'push_token' => null]);
        $this->audit->security('token_revoked', $account->organization_id, $account, null, ['device' => $device->id]);
    }

    public function revokeAll(PatientPortalAccount $account, string $reason): void
    {
        $account->tokens()->delete();
        $account->devices()->update(['revoked_at' => now(), 'revoked_reason' => $reason, 'trusted_at' => null, 'token_id' => null, 'push_token' => null]);
        $account->forceFill(['remember_token' => null])->save();
    }

    /** Same answer for a known and an unknown number. */
    public function startReset(string $phoneInput): array
    {
        $challenge = Str::random(40);
        $phone = PhoneNumber::normalize($phoneInput);
        $this->throttleStart('reset_start', PhoneNumber::hash($phone));
        $account = $phone ? PatientPortalAccount::query()->where('phone', $phone)->whereIn('status', ['active', 'locked'])->first() : null;
        if ($account) {
            $account->forceFill(['invitation_hash' => hash('sha256', $challenge), 'invitation_expires_at' => now()->addMinutes($this->policy->int('otp.ttl_minutes', 10) + 20)])->save();
            $this->otp->issue($account->organization_id, $phone, 'reset', $account, $account->patient_id, $account->language);
        }

        return ['challenge' => $challenge];
    }

    public function completeReset(string $challenge, string $code, string $password): PatientPortalAccount
    {
        $this->assertPassword($password);
        $account = PatientPortalAccount::query()->where('invitation_hash', hash('sha256', $challenge))->whereIn('status', ['active', 'locked'])->where('invitation_expires_at', '>', now())->first();
        if ($account === null || ! $this->otp->verify((string) $account->phone, 'reset', $code)) {
            throw new PortalException(__('The code is not right or has expired.'));
        }
        $account->forceFill(['password' => $password, 'status' => 'active', 'failed_attempts' => 0, 'locked_until' => null, 'invitation_hash' => null, 'invitation_expires_at' => null])->save();
        $this->revokeAll($account, 'password reset');
        $this->audit->security('password_reset', $account->organization_id, $account, PhoneNumber::hash($account->phone));

        return $account;
    }

    public function changePassword(PatientPortalAccount $account, string $current, string $new): void
    {
        $this->assertPassword($new);
        if (! Hash::check($current, (string) $account->password)) {
            throw new PortalException(__('The current password is not right.'));
        }
        $account->forceFill(['password' => $new])->save();
    }

    /** Staff actions on a login. */
    public function setStatus(PatientPortalAccount $account, string $status, ?string $reason = null): void
    {
        abort_unless(in_array($status, ['active', 'locked', 'suspended', 'closed'], true), 422);
        $account->forceFill(['status' => $status, 'is_active' => $status === 'active', 'locked_until' => $status === 'active' ? null : $account->locked_until, 'failed_attempts' => 0]
            + ($status === 'closed' ? ['closed_at' => now(), 'closure_reason' => $reason] : []))->save();
        if ($status !== 'active') {
            $this->revokeAll($account, "login {$status}");
        }
        $this->auditLog->log('UPDATE', PatientPortalAccount::class, $account->id, null, ['status' => $status, 'reason' => $reason]);
    }

    /** The limit is counted on the phone number and the address whether or not anyone matched, so a 'wait' answer tells a stranger nothing. */
    private function throttleStart(string $kind, ?string $phoneHash): void
    {
        $ip = request()->ip();
        $last = PortalSecurityEvent::query()->where('kind', $kind)->where('phone_hash', $phoneHash)->latest('id')->first();
        if ($phoneHash !== null && $last && $last->created_at->gt(now()->subSeconds($this->policy->int('otp.resend_after_seconds', 60)))
            || $this->audit->countRecent($kind, 'phone_hash', $phoneHash, 60) >= $this->policy->int('otp.max_per_phone_per_hour', 5)
            || $this->audit->countRecent($kind, 'ip', $ip, 60) >= $this->policy->int('otp.max_per_ip_per_hour', 20)) {
            $this->audit->security('rate_limited', null, null, $phoneHash, ['kind' => $kind]);
            throw new PortalException(__('Please wait a little before asking again.'), 429);
        }
        $this->audit->security($kind, null, null, $phoneHash);
    }

    private function match(array $in, string $phone): ?Patient
    {
        $organization = $this->organization($in['hospital'] ?? null);
        if ($organization === null) {
            return null;
        }
        $identifier = trim((string) ($in['identifier'] ?? ''));
        if ($identifier === '') {
            return null;
        }
        $patient = Patient::query()->where('organization_id', $organization->id)->where(fn ($q) => $q->where('enterprise_patient_no', $identifier)->orWhere('national_identifier', $identifier))->first();
        if ($patient === null || $patient->date_of_birth === null || $patient->date_of_birth->toDateString() !== $this->date($in['date_of_birth'] ?? '')) {
            return null;
        }
        if (! PhoneNumber::same($patient->phone, $phone) || $patient->deceased_at !== null || $patient->merged_into_patient_id !== null) {
            return null;
        }
        if ($patient->date_of_birth->age < $this->policy->int('registration.minimum_age_years', 16)) {
            return null;
        }

        return $patient;
    }

    private function organization(?string $code): ?Organization
    {
        $q = Organization::query()->where('is_active', true);
        if ($code !== null && $code !== '') {
            return $q->where(fn ($w) => $w->where('code', $code)->orWhere('slug', $code))->first();
        }

        return $q->count() === 1 ? $q->first() : null;
    }

    private function date(string $value): ?string
    {
        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make(Str::random(24));
    }

    private function deviceHash(string $uid): string
    {
        return hash('sha256', $uid);
    }

    private function assertPassword(string $password): void
    {
        $min = $this->policy->int('login.password_min_length', 8);
        if (mb_strlen($password) < $min || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
            throw ValidationException::withMessages(['password' => __('The password needs at least :n characters with letters and numbers.', ['n' => $min])]);
        }
    }
}
