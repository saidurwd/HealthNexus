<?php

namespace App\Services\Hr;

use App\Models\Hr\HrCredential;
use App\Models\Hr\HrEmployee;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/** Professional licences and certificates (S02): recorded, verified, watched for expiry. Refusing duty to a lapsed licence is a policy the hospital turns on. */
class CredentialService
{
    public function __construct(private readonly HrPolicy $policy, private readonly AlertService $alerts) {}

    public function add(HrEmployee $employee, string $type, ?string $number, ?string $issuingBody, ?string $issuedOn, ?string $expiresOn): HrCredential
    {
        if (! in_array($type, (array) $this->policy->get('credential_types'), true)) {
            throw ValidationException::withMessages(['credential_type' => 'Not a known credential type.']);
        }
        if ($issuedOn && $expiresOn && $expiresOn < $issuedOn) {
            throw ValidationException::withMessages(['expires_on' => 'It expires before it was issued.']);
        }

        return HrCredential::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'credential_type' => $type, 'number' => $number, 'issuing_body' => $issuingBody, 'issued_on' => $issuedOn, 'expires_on' => $expiresOn, 'status' => $expiresOn && $expiresOn < now()->toDateString() ? 'expired' : 'active']);
    }

    public function verify(HrCredential $credential, User $by): HrCredential
    {
        $credential->update(['verified_by' => $by->id, 'verified_at' => now()]);

        return $credential->refresh();
    }

    public function suspend(HrCredential $credential, bool $revoke = false): HrCredential
    {
        $credential->update(['status' => $revoke ? 'revoked' : 'suspended']);

        return $credential->refresh();
    }

    /** Credential types the person's job requires but they do not hold in date. @return list<string> */
    public function gaps(HrEmployee $employee, ?CarbonInterface $on = null): array
    {
        $on ??= now();
        $required = (array) ($employee->job->required_credentials ?? []);
        $gaps = [];
        foreach ($required as $type) {
            $ok = $employee->credentials()->where('credential_type', $type)->where('status', 'active')->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', $on->toDateString()))->exists();
            $ok || $gaps[] = $type;
        }

        return $gaps;
    }

    /** True when the hospital's policy allows this person to be put on duty on that day. */
    public function canBeRostered(HrEmployee $employee, CarbonInterface $on): bool
    {
        return ! $this->policy->get('block_expired_credentials') || $this->gaps($employee, $on) === [];
    }

    /** Marks lapsed credentials expired and warns about those about to. @return array{expired: int, alerts: int} */
    public function sweep(int $organizationId, array $recipientUserIds = []): array
    {
        $expired = HrCredential::query()->where('organization_id', $organizationId)->where('status', 'active')->whereNotNull('expires_on')->whereDate('expires_on', '<', now()->toDateString())->update(['status' => 'expired']);
        $alerts = 0;
        $windows = collect((array) $this->policy->get('credential_alert_days'))->sort()->values();
        $widest = (int) $windows->max();
        HrCredential::query()->where('organization_id', $organizationId)->where('status', 'active')->whereNotNull('expires_on')->whereBetween('expires_on', [now()->toDateString(), now()->addDays($widest)->toDateString()])
            ->whereHas('employee', fn ($q) => $q->whereIn('status', ['active', 'on_leave', 'onboarding']))->with('employee')->each(function (HrCredential $credential) use ($windows, $recipientUserIds, &$alerts, $organizationId) {
                $days = (int) now()->startOfDay()->diffInDays($credential->expires_on, false);
                $window = $windows->first(fn ($w) => $days <= $w);
                if ($window === null) {
                    return;
                }
                $alert = $this->alerts->raise(new AlertRequest(
                    organizationId: $organizationId, category: 'credential_expiry', severity: $window <= 7 ? 'critical' : 'warning', sourceModule: 'hr',
                    message: "{$credential->employee->fullName()}: {$credential->credential_type} expires in $days day(s).", sourceType: HrCredential::class, sourceId: $credential->id,
                    recipientUserIds: $recipientUserIds, ackRequired: false, dedupKey: "hr-cred:{$credential->id}:$window",
                ));
                $alert->wasRecentlyCreated && $alerts++;
            });

        return ['expired' => $expired, 'alerts' => $alerts];
    }
}
