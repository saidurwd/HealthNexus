<?php

namespace App\Services\Ot;

use App\Events\Ot\ConsentFinalized;
use App\Models\Ot\OtSurgery;
use App\Models\PatientConsent;
use App\Models\User;
use App\Services\Patients\PatientService;

/**
 * Thin wrapper over the existing PatientService consent methods (decision #2) — never a parallel
 * ot_consents table. witness_name/language are set via a direct update immediately after
 * PatientService::grantConsent() creates the row, inside the same request — not a later silent
 * edit of a finalized record.
 */
class OtConsentService
{
    public const CONSENT_TYPE = 'surgical_procedure';

    public function __construct(private readonly PatientService $patients) {}

    public function grant(OtSurgery $surgery, User $grantedBy, ?string $notes = null, ?string $witnessName = null, ?string $language = null): PatientConsent
    {
        $consent = $this->patients->grantConsent($surgery->patient, self::CONSENT_TYPE, $grantedBy, $notes);

        if ($witnessName || $language) {
            $consent->update(array_filter([
                'witness_name' => $witnessName,
                'language' => $language,
            ]));
        }

        $surgery->update(['consent_id' => $consent->id]);

        event(new ConsentFinalized($consent));

        return $consent->refresh();
    }

    public function withdraw(PatientConsent $consent, User $withdrawnBy): PatientConsent
    {
        return $this->patients->withdrawConsent($consent, $withdrawnBy);
    }
}
