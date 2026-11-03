<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\PatientPortalAccount;
use App\Services\Patients\PatientService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** The patient's own consents (S08). Uses the Phase 1 consent record and its versioning; the granting user is the portal service user, the real actor is in the note and audit. */
class ConsentService
{
    public function __construct(private readonly PatientService $patients, private readonly PortalSystemUser $system, private readonly PortalPolicy $policy, private readonly PortalAudit $audit) {}

    /** @return Collection<int, array{type: string, active: bool, since: Carbon|null}> */
    public function list(Patient $patient): Collection
    {
        $current = PatientConsent::query()->where('patient_id', $patient->id)->where('status', 'active')->get()->keyBy('consent_type');

        return collect(array_values((array) $this->policy->get('consent_types', [])))->map(fn ($type): array => ['type' => (string) $type, 'active' => $current->has($type), 'since' => $current->get($type)?->granted_at])->values();
    }

    public function set(PatientPortalAccount $account, Patient $patient, string $type, bool $granted): void
    {
        if (! in_array($type, (array) $this->policy->get('consent_types', []), true)) {
            throw new PortalException(__('Unknown consent.'));
        }
        $user = $this->system->for($patient->organization_id);
        $active = PatientConsent::query()->where(['patient_id' => $patient->id, 'consent_type' => $type, 'status' => 'active'])->first();
        if ($granted && ! $active) {
            $this->patients->grantConsent($patient, $type, $user, 'Granted by the patient online (account '.$account->public_id.')');
        } elseif (! $granted && $active) {
            $this->patients->withdrawConsent($active, $user);
        }
        $this->audit->access($account, $patient->id, $granted ? 'consent_grant' : 'consent_withdraw');
    }
}
