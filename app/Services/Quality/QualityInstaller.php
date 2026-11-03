<?php

namespace App\Services\Quality;

use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaPrivilegeCatalog;
use App\Models\Quality\QaStandard;
use App\Services\Quality\Starter\StarterStandard;

/** The content a hospital starts with: indicators, committees, a privilege list and a plain checklist of requirements. All of it is an example for the hospital to edit. Safe to run again. */
class QualityInstaller
{
    public function __construct(private readonly IndicatorService $indicators, private readonly PrivilegeService $privileges, private readonly AccreditationService $accreditation, private readonly MeetingService $meetings) {}

    /** @return array{indicators:int,committees:int,privileges:int,standards:int} */
    public function install(?int $organizationId = null): array
    {
        $out = ['indicators' => $this->indicators->install(), 'committees' => 0, 'privileges' => 0, 'standards' => 0];
        if ($organizationId === null) {
            return $out;
        }
        foreach ([['Patient Safety Committee', 'safety', 'Reviews serious incidents, trends and the actions that follow.'], ['Infection Prevention and Control Committee', 'infection', 'Reviews infections, outbreaks and hand hygiene.'], ['Morbidity and Mortality Meeting', 'mm', 'Learns from deaths and unexpected outcomes.']] as [$name, $kind, $purpose]) {
            if (! QaCommittee::query()->where(['organization_id' => $organizationId, 'name' => $name])->exists()) {
                $this->meetings->committee($organizationId, $name, $kind, null, [], $purpose);
                $out['committees']++;
            }
        }
        foreach (StarterStandard::privileges() as [$code, $name, $credential, $months]) {
            $existing = QaPrivilegeCatalog::query()->where(['organization_id' => $organizationId, 'code' => $code])->exists();
            $this->privileges->addToCatalog($organizationId, $code, $name, $credential, $months);
            $existing || $out['privileges']++;
        }
        if (! QaStandard::query()->where(['organization_id' => $organizationId, 'code' => StarterStandard::CODE])->exists()) {
            $this->accreditation->addStandard($organizationId, StarterStandard::CODE, StarterStandard::NAME, StarterStandard::EDITION, StarterStandard::items());
            $out['standards']++;
        }

        return $out;
    }
}
