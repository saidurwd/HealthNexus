<?php

namespace App\Services\Portal;

use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalSecurityEvent;
use Illuminate\Database\Eloquent\Model;

/** Two append-only trails: what a patient (or a relative acting for them) looked at or downloaded, and what happened around logging in. */
class PortalAudit
{
    public function access(PatientPortalAccount $account, int $patientId, string $action, ?Model $subject = null): void
    {
        PortalAccessLog::create([
            'organization_id' => $account->organization_id,
            'account_id' => $account->id,
            'patient_id' => $patientId,
            'as_proxy' => $patientId !== $account->patient_id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function security(string $kind, ?int $organizationId = null, ?PatientPortalAccount $account = null, ?string $phoneHash = null, array $detail = []): void
    {
        PortalSecurityEvent::create([
            'organization_id' => $organizationId ?? $account?->organization_id,
            'account_id' => $account?->id,
            'kind' => $kind,
            'phone_hash' => $phoneHash,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 200),
            'detail' => $detail ?: null,
            'created_at' => now(),
        ]);
    }

    public function countRecent(string $kind, string $column, ?string $value, int $minutes): int
    {
        if ($value === null) {
            return 0;
        }

        return PortalSecurityEvent::query()->where('kind', $kind)->where($column, $value)->where('created_at', '>=', now()->subMinutes($minutes))->count();
    }
}
