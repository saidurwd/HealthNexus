<?php

namespace App\Interop\Mappers;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Database\Eloquent\Model;

class AllergyIntoleranceMapper extends Mapper
{
    public function type(): string
    {
        return 'AllergyIntolerance';
    }

    public function sources(): array
    {
        return [['model' => PatientAllergy::class, 'entity' => 'PatientAllergy', 'patient' => 'patient_id', 'search' => ['clinical-status' => fn ($q, $v) => $q->where('is_active', $v === 'active')]]];
    }

    /** @param PatientAllergy $m */
    public function toFhir(Model $m): array
    {
        return array_filter($this->base($m) + [
            'clinicalStatus' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical', $m->is_active ? 'active' : 'inactive')]],
            'type' => 'allergy',
            'criticality' => match (strtolower((string) $m->severity)) {
                'severe' => 'high', 'mild' => 'low', default => 'unable-to-assess'
            },
            'code' => ['text' => $m->substance],
            'patient' => $this->ref('Patient', Patient::class, $m->patient_id),
            'note' => $m->notes ? [['text' => $m->notes]] : null,
            'reaction' => $m->reaction ? [['manifestation' => [['text' => $m->reaction]], 'severity' => match (strtolower((string) $m->severity)) {
                'severe' => 'severe', 'mild' => 'mild', default => 'moderate'
            }]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
