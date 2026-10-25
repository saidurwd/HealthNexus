<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUProcedureCompleted;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuProcedureRecord;
use App\Models\User;
use App\Services\Patients\PatientService;

/**
 * Reuses the existing PatientConsent framework where a procedure requires consent — never a
 * second consent engine (spec §37, same decision OT made for surgical consent in Phase 10).
 */
class IcuProcedureService
{
    public const CONSENT_TYPE = 'icu_procedure';

    public function __construct(private readonly PatientService $patients) {}

    public function record(IcuEpisode $episode, array $data, User $user, bool $grantConsent = false): IcuProcedureRecord
    {
        $consentId = null;

        if ($grantConsent) {
            $consent = $this->patients->grantConsent($episode->patient, self::CONSENT_TYPE, $user, $data['consent_notes'] ?? null);
            $consentId = $consent->id;
        }

        $record = IcuProcedureRecord::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'consent_id' => $consentId,
            'performed_at' => $data['performed_at'] ?? now(),
            'performed_by' => $user->id,
        ]);

        event(new ICUProcedureCompleted($record));

        return $record;
    }
}
