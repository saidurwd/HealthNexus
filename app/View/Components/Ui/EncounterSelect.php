<?php

namespace App\View\Components\Ui;

use App\Models\Encounter;

/** The visits of the patient chosen in another dropdown (give it with depends-on), or any visit when there is none. */
class EncounterSelect extends LookupSelect
{
    protected function route(): string
    {
        return 'admin.lookup.encounters';
    }

    protected function defaultPlaceholder(): string
    {
        return $this->dependsOn ? __('Choose the patient first, then the visit') : __('Type a visit number or patient');
    }

    protected function label(int $id, int $organizationId): ?string
    {
        $e = Encounter::query()->where('organization_id', $organizationId)->find($id);

        return $e ? $e->encounter_no.' · '.$e->encounter_type.' · '.$this->date($e->started_at ?? $e->created_at) : null;
    }
}
