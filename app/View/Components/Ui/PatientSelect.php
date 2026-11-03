<?php

namespace App\View\Components\Ui;

use App\Models\Patient;

class PatientSelect extends LookupSelect
{
    protected function route(): string
    {
        return 'admin.lookup.patients';
    }

    protected function defaultPlaceholder(): string
    {
        return __('Type a name, patient number or phone');
    }

    protected function label(int $id, int $organizationId): ?string
    {
        $p = Patient::query()->where('organization_id', $organizationId)->find($id);

        return $p ? $p->full_name.' ('.($p->enterprise_patient_no ?? 'N/A').')' : null;
    }
}
