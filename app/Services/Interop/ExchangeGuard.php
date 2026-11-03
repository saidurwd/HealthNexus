<?php

namespace App\Services\Interop;

use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\User;

/**
 * May this patient's data go to / come from this partner? (S11) A partner flagged `requires_consent` gets nothing unless the patient has an active consent of the partner's type. A person with
 * the right permission can override in an emergency by giving a reason; the override and every refusal are written to the access log, which cannot be edited.
 */
class ExchangeGuard
{
    public function consentOk(?IntegrationPartner $partner, Patient $patient): bool
    {
        if ($partner === null || ! $partner->requires_consent) {
            return true;
        }
        $type = $partner->consent_type ?: 'data_sharing';

        return PatientConsent::query()->where(['patient_id' => $patient->id, 'consent_type' => $type, 'status' => 'active'])->exists();
    }

    /** @return bool allowed (and logged either way) */
    public function check(?IntegrationPartner $partner, Patient $patient, string $resourceType, string $action, ?string $resourceId = null, ?User $user = null, ?string $overrideReason = null): bool
    {
        if ($partner !== null && $patient->organization_id !== $partner->organization_id) {
            $this->log($partner, $patient, $resourceType, $resourceId, $action, 'denied_scope', 'other hospital', $user);

            return false;
        }
        if ($this->consentOk($partner, $patient)) {
            $this->log($partner, $patient, $resourceType, $resourceId, $action, 'allowed', null, $user);

            return true;
        }
        if ($overrideReason !== null && trim($overrideReason) !== '' && $user !== null && $user->can('interoperability.consent.override')) {
            $this->log($partner, $patient, $resourceType, $resourceId, $action, 'override', mb_substr($overrideReason, 0, 200), $user);

            return true;
        }
        $this->log($partner, $patient, $resourceType, $resourceId, $action, 'denied_consent', 'no active consent', $user);

        return false;
    }

    public function log(?IntegrationPartner $partner, ?Patient $patient, ?string $resourceType, ?string $resourceId, string $action, string $outcome, ?string $reason = null, ?User $user = null): void
    {
        IntegrationAccessLog::create(['organization_id' => $partner->organization_id ?? $patient->organization_id ?? $user?->organizations()->value('organizations.id'), 'partner_id' => $partner?->id, 'user_id' => $user?->id, 'patient_id' => $patient?->id,
            'resource_type' => $resourceType, 'resource_id' => $resourceId, 'action' => $action, 'outcome' => $outcome, 'reason' => $reason, 'ip' => request()->ip(), 'created_at' => now()]);
    }
}
