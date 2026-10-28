<?php

namespace App\Services\Portal;

use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalOtp;
use App\Models\Portal\PortalSecurityEvent;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;

/**
 * Closing a login and keeping housekeeping (S12). Closing a portal login never deletes anything clinical or financial: it ends access, signs every device out and removes what exists
 * only to run the login (codes, push tokens). Retention periods are examples in config/portal.php for the privacy officer to confirm.
 */
class PortalRetentionService
{
    public function __construct(private readonly PortalAuthService $auth, private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    /** The patient closes their own login: they must give the password. */
    public function closeOwn(PatientPortalAccount $account, string $password, ?string $reason): void
    {
        if (! Hash::check($password, (string) $account->password)) {
            throw new PortalException(__('The password is not right.'));
        }
        $this->auth->setStatus($account, 'closed', $reason ?: 'closed by the patient');
        $account->patient()->update(['portal_enabled' => false]);
        $this->audit->log('UPDATE', PatientPortalAccount::class, $account->id, null, ['closed_by' => 'patient']);
    }

    /** @return array{otps:int,events:int,closed_devices:int} */
    public function purge(): array
    {
        $otps = PortalOtp::query()->where('created_at', '<', now()->subDays($this->policy->int('retention.otp_days', 2)))->delete();
        $events = PortalSecurityEvent::query()->where('created_at', '<', now()->subDays($this->policy->int('retention.security_event_days', 365)))->delete();
        $devices = 0;
        PatientPortalAccount::query()->where('status', 'closed')->where('closed_at', '<', now()->subDays($this->policy->int('retention.closed_account_days', 90)))->each(function (PatientPortalAccount $a) use (&$devices) {
            $devices += $a->devices()->whereNotNull('push_token')->count();
            $a->devices()->update(['push_token' => null]);
        });

        return ['otps' => $otps, 'events' => $events, 'closed_devices' => $devices];
    }
}
