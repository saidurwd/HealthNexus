<?php

namespace App\Services\Portal;

use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalOtp;
use Illuminate\Support\Facades\DB;

/** One-time codes by SMS. The code is kept as a keyed hash; the number it was sent to is always the one on the patient record, never one the caller typed. */
class OtpService
{
    public function __construct(private readonly CommunicationService $comms, private readonly PortalPolicy $policy, private readonly PortalAudit $audit) {}

    /** @throws PortalException when asked too often */
    public function issue(int $organizationId, string $target, string $purpose, ?PatientPortalAccount $account, ?int $patientId, string $language = 'bn'): void
    {
        $hash = PhoneNumber::hash($target);
        if ($this->audit->countRecent('otp_sent', 'phone_hash', $hash, 60) >= $this->policy->int('otp.max_per_phone_per_hour', 5)
            || $this->audit->countRecent('otp_sent', 'ip', request()->ip(), 60) >= $this->policy->int('otp.max_per_ip_per_hour', 20)) {
            $this->audit->security('rate_limited', $organizationId, $account, $hash, ['purpose' => $purpose]);
            throw new PortalException(__('Too many codes were asked for. Please wait a while and try again.'), 429);
        }
        $last = PortalOtp::query()->where(['target' => $target, 'purpose' => $purpose])->latest('id')->first();
        if ($last && $last->created_at->gt(now()->subSeconds($this->policy->int('otp.resend_after_seconds', 60)))) {
            throw new PortalException(__('A code was just sent. Please wait a minute before asking again.'), 429);
        }

        $length = max(4, $this->policy->int('otp.length', 6));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl = $this->policy->int('otp.ttl_minutes', 10);
        DB::transaction(function () use ($organizationId, $target, $purpose, $account, $code, $ttl) {
            PortalOtp::query()->where(['target' => $target, 'purpose' => $purpose])->whereNull('consumed_at')->update(['consumed_at' => now()]);
            PortalOtp::create(['organization_id' => $organizationId, 'target' => $target, 'purpose' => $purpose, 'account_id' => $account?->id, 'code_hash' => $this->hash($target, $purpose, $code), 'expires_at' => now()->addMinutes($ttl), 'ip' => request()->ip(), 'created_at' => now()]);
        });
        $this->audit->security('otp_sent', $organizationId, $account, $hash, ['purpose' => $purpose]);

        $body = $language === 'en'
            ? "HealthNexus code: $code. It works for $ttl minutes. Do not share it with anyone."
            : "হেলথনেক্সাস কোড: {$code}। এটি {$ttl} মিনিট কাজ করবে। কাউকে জানাবেন না।";
        $this->comms->send(['organization_id' => $organizationId, 'patient_id' => $patientId, 'channel' => 'sms', 'purpose' => 'transactional', 'template' => 'otp', 'to' => $target, 'body' => $body]);
    }

    public function verify(string $target, string $purpose, string $code): bool
    {
        return DB::transaction(function () use ($target, $purpose, $code) {
            $otp = PortalOtp::query()->where(['target' => $target, 'purpose' => $purpose])->whereNull('consumed_at')->where('expires_at', '>', now())->latest('id')->lockForUpdate()->first();
            if ($otp === null) {
                return false;
            }
            if ($otp->attempts >= $this->policy->int('otp.max_attempts', 5)) {
                $otp->update(['consumed_at' => now()]);

                return false;
            }
            $otp->increment('attempts');
            if (! hash_equals($otp->code_hash, $this->hash($target, $purpose, $code))) {
                $this->audit->security('otp_fail', $otp->organization_id, null, PhoneNumber::hash($target), ['purpose' => $purpose]);

                return false;
            }
            $otp->update(['consumed_at' => now()]);

            return true;
        });
    }

    private function hash(string $target, string $purpose, string $code): string
    {
        return hash_hmac('sha256', "$target|$purpose|$code", (string) config('app.key'));
    }
}
