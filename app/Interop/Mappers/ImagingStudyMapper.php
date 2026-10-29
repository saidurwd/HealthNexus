<?php

namespace App\Interop\Mappers;

use App\Models\Patient;
use App\Models\Radiology\RadiologyStudy;
use Illuminate\Database\Eloquent\Model;

class ImagingStudyMapper extends Mapper
{
    public function type(): string
    {
        return 'ImagingStudy';
    }

    public function sources(): array
    {
        return [['model' => RadiologyStudy::class, 'entity' => 'RadiologyStudy', 'patient' => 'patient_id', 'search' => [
            'identifier' => fn ($q, $v) => $q->where(fn ($w) => $w->where('accession_number', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v)->orWhere('study_instance_uid', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v)),
            'modality' => fn ($q, $v) => $q->where('modality', $v),
        ]]];
    }

    /** @param RadiologyStudy $m */
    public function toFhir(Model $m): array
    {
        return array_filter($this->base($m) + [
            'identifier' => array_values(array_filter([$m->study_instance_uid ? ['system' => 'urn:dicom:uid', 'value' => 'urn:oid:'.$m->study_instance_uid] : null, $m->accession_number ? ['type' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v2-0203', 'ACSN')]], 'system' => 'urn:healthnexus:accession', 'value' => $m->accession_number] : null])),
            'status' => 'available',
            'modality' => $m->modality ? [$this->coding('http://dicom.nema.org/resources/ontology/DCM', (string) $m->modality)] : null,
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'started' => $this->date($m->study_date),
            'numberOfSeries' => $m->number_of_series,
            'numberOfInstances' => $m->number_of_instances,
            'description' => $m->study_description,
        ], fn ($v) => $v !== null && $v !== []);
    }
}
