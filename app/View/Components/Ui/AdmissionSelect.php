<?php

namespace App\View\Components\Ui;

use App\Models\Ipd\IpdAdmission;

/** The inpatient admissions of the patient chosen in another dropdown (give it with depends-on), or any admission when there is none. */
class AdmissionSelect extends LookupSelect
{
    protected function route(): string
    {
        return 'admin.lookup.admissions';
    }

    protected function defaultPlaceholder(): string
    {
        return $this->dependsOn ? __('Choose the patient first, then the admission') : __('Type an admission number or patient');
    }

    protected function label(int $id, int $organizationId): ?string
    {
        $a = IpdAdmission::query()->where('organization_id', $organizationId)->find($id);

        return $a ? $a->admission_number.' · '.str_replace('_', ' ', (string) $a->status).' · '.$this->date($a->admitted_at) : null;
    }
}
