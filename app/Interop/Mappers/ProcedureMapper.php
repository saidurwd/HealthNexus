<?php

namespace App\Interop\Mappers;

use App\Models\Encounter;
use App\Models\EncounterProcedure;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

class ProcedureMapper extends Mapper
{
    public function type(): string
    {
        return 'Procedure';
    }

    public function sources(): array
    {
        return [['model' => EncounterProcedure::class, 'entity' => 'EncounterProcedure', 'patient' => 'patient_id', 'search' => ['date' => fn ($q, $v) => $q->whereDate('procedure_date', $v)]]];
    }

    /** @param EncounterProcedure $m */
    public function toFhir(Model $m): array
    {
        return array_filter($this->base($m, 'EncounterProcedure') + [
            'status' => match ($m->status) {
                'cancelled' => 'not-done', 'planned' => 'preparation', 'entered_in_error' => 'entered-in-error', default => 'completed'
            },
            'code' => ['coding' => $m->procedure_code ? [$this->coding('urn:healthnexus:procedure', (string) $m->procedure_code, $m->procedure_name)] : [], 'text' => $m->procedure_name],
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'encounter' => $this->ref('Encounter', Encounter::class, $m->encounter_id),
            'performedDateTime' => $m->procedure_date?->format('Y-m-d'),
            'note' => $m->notes ? [['text' => $m->notes]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
