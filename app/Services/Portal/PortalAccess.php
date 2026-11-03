<?php

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalProxy;
use Illuminate\Support\Collection;

/**
 * Whose records may this login open, and which parts? Own record: everything. A relative's record: only the scopes staff approved, only while the approval is in date,
 * and, for a young person, never the parts the config hides from a parent (guideline override: proxy sees only what the guardian rules allow).
 */
class PortalAccess
{
    public function __construct(private readonly PortalPolicy $policy) {}

    /** @return Collection<int, array{patient:Patient,relation:string,scopes:list<string>,proxy:?PortalProxy}> */
    public function subjects(PatientPortalAccount $account): Collection
    {
        $own = $account->patient;
        $list = collect();
        if ($own && $own->deceased_at === null) {
            $list->push(['patient' => $own, 'relation' => 'self', 'scopes' => array_merge((array) $this->policy->get('proxy.scopes', []), ['consents', 'preferences', 'proxies', 'feedback']), 'proxy' => null]);
        }

        PortalProxy::query()->where('account_id', $account->id)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString()))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()->toDateString()))
            ->get()->each(function (PortalProxy $proxy) use ($list) {
                $patient = Patient::query()->find($proxy->patient_id);
                if ($patient === null || $patient->deceased_at !== null || $patient->organization_id !== $proxy->organization_id) {
                    return;
                }
                $scopes = (array) $proxy->scopes;
                $age = $patient->date_of_birth?->age;
                if ($age !== null && $age >= $this->policy->int('proxy.adolescent_age_from', 13) && $age < $this->policy->int('proxy.minor_age_years', 18) && in_array($proxy->relationship, ['parent', 'guardian'], true)) {
                    $scopes = array_values(array_diff($scopes, (array) $this->policy->get('proxy.adolescent_hidden_scopes', [])));
                }
                $list->push(['patient' => $patient, 'relation' => $proxy->relationship, 'scopes' => $scopes, 'proxy' => $proxy]);
            });

        return $list;
    }

    /** @throws PortalException 403 when the login may not open that person for that purpose */
    public function patientFor(PatientPortalAccount $account, ?string $patientPublicId, string $scope): Patient
    {
        $subject = $this->subjects($account)->first(fn ($s) => $patientPublicId === null || $patientPublicId === '' ? $s['relation'] === 'self' : $s['patient']->public_id === $patientPublicId);
        if ($subject === null || ! in_array($scope, $subject['scopes'], true)) {
            throw new PortalException(__('You do not have access to that.'), 403);
        }

        return $subject['patient'];
    }

    /** Ids of every patient this login may open for the scope (own record included). */
    public function patientIds(PatientPortalAccount $account, string $scope): array
    {
        return $this->subjects($account)->filter(fn ($s) => in_array($scope, $s['scopes'], true))->map(fn ($s) => $s['patient']->id)->values()->all();
    }
}
