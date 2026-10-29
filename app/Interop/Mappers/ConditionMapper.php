<?php

namespace App\Interop\Mappers;

use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientProblem;
use Illuminate\Database\Eloquent\Model;

/** Diagnoses made at a visit and problems on the patient's list. */
class ConditionMapper extends Mapper
{
    public function type(): string
    {
        return 'Condition';
    }

    public function sources(): array
    {
        return [
            ['model' => Diagnosis::class, 'entity' => 'Diagnosis', 'patient' => 'patient_id', 'search' => ['code' => fn ($q, $v) => $q->where('code', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v)]],
            ['model' => PatientProblem::class, 'entity' => 'PatientProblem', 'patient' => 'patient_id', 'search' => ['code' => fn ($q, $v) => $q->where('problem_code', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v)]],
        ];
    }

    private function system(?string $name): string
    {
        $key = match (strtolower((string) $name)) {
            'icd-10', 'icd10', 'icd_10' => 'icd10', 'icd-11', 'icd11' => 'icd11', 'snomed', 'snomedct', 'snomed-ct' => 'snomed', default => null
        };

        return $key ? $this->terminology->uri($key, match ($key) {
            'icd10' => 'http://hl7.org/fhir/sid/icd-10', 'icd11' => 'http://id.who.int/icd/release/11/mms', default => 'http://snomed.info/sct'
        }) : 'urn:healthnexus:local-code';
    }

    /** @param Diagnosis|PatientProblem $m */
    public function toFhir(Model $m): array
    {
        if ($m instanceof PatientProblem) {
            return array_filter($this->base($m, 'PatientProblem') + [
                'clinicalStatus' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/condition-clinical', $m->status === 'active' ? 'active' : ($m->status === 'resolved' ? 'resolved' : 'inactive'))]],
                'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/condition-category', 'problem-list-item')]]],
                'code' => ['coding' => [$this->coding($this->system($m->coding_system), (string) $m->problem_code, $m->problem_name)], 'text' => $m->problem_name],
                'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
                'onsetDateTime' => $m->onset_date?->format('Y-m-d'),
                'abatementDateTime' => $m->resolved_date?->format('Y-m-d'),
            ], fn ($v) => $v !== null && $v !== []);
        }

        return array_filter($this->base($m, 'Diagnosis') + [
            'clinicalStatus' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/condition-clinical', in_array($m->status, ['resolved', 'inactive'], true) ? 'resolved' : 'active')]],
            'verificationStatus' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/condition-ver-status', $m->diagnosis_type === 'differential' ? 'differential' : 'confirmed')]],
            'category' => [['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/condition-category', 'encounter-diagnosis')]]],
            'code' => ['coding' => [$this->coding($this->system($m->coding_system), (string) $m->code, $m->description)], 'text' => $m->description],
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'encounter' => $this->ref('Encounter', Encounter::class, $m->encounter_id),
            'recordedDate' => $this->time($m->recorded_at),
        ], fn ($v) => $v !== null && $v !== []);
    }
}
