<?php

namespace App\Interop\Mappers;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientAddress;
use Illuminate\Database\Eloquent\Model;

class PatientMapper extends Mapper
{
    public function type(): string
    {
        return 'Patient';
    }

    public function sources(): array
    {
        return [['model' => Patient::class, 'entity' => 'Patient', 'patient' => 'id', 'search' => [
            'identifier' => fn ($q, $v) => $q->where(function ($w) use ($v) {
                $value = str_contains($v, '|') ? explode('|', $v, 2)[1] : $v;
                $w->where('enterprise_patient_no', $value)->orWhere('national_identifier', $value);
            }),
            'family' => fn ($q, $v) => $q->where('last_name', 'like', addcslashes($v, '%_\\').'%'),
            'given' => fn ($q, $v) => $q->where('first_name', 'like', addcslashes($v, '%_\\').'%'),
            'birthdate' => fn ($q, $v) => $q->whereDate('date_of_birth', $v),
            'name' => fn ($q, $v) => $q->where(fn ($w) => $w->where('last_name', 'like', '%'.addcslashes($v, '%_\\').'%')->orWhere('first_name', 'like', '%'.addcslashes($v, '%_\\').'%')),
            'gender' => fn ($q, $v) => $q->whereIn('sex', match ($v) {
                'male' => ['M', 'male'], 'female' => ['F', 'female'], 'other' => ['O', 'other'], default => ['__none__']
            }),
        ]]];
    }

    /** Merged-away and temporary records are not served. */
    public function visible(Patient $p): bool
    {
        return $p->merged_into_patient_id === null;
    }

    /** @param Patient $m */
    public function toFhir(Model $m): array
    {
        $ids = [['use' => 'usual', 'type' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v2-0203', 'MR', 'Medical record number')]], 'system' => config('interop.fhir.patient_identifier_systems.mrn'), 'value' => $m->enterprise_patient_no]];
        if ($m->national_identifier) {
            $ids[] = ['use' => 'official', 'type' => ['coding' => [$this->coding('http://terminology.hl7.org/CodeSystem/v2-0203', 'NI', 'National unique individual identifier')]], 'system' => config('interop.fhir.patient_identifier_systems.national_id'), 'value' => $m->national_identifier];
        }
        $gender = match (strtoupper((string) $m->sex)) {
            'M' => 'male', 'F' => 'female', 'O' => 'other', default => 'unknown',
        };

        return array_filter($this->base($m) + [
            'identifier' => $ids,
            'active' => $m->status === 'active',
            'name' => [array_filter(['use' => 'official', 'family' => $m->last_name, 'given' => array_values(array_filter([$m->first_name, $m->middle_name])), 'text' => trim($m->first_name.' '.$m->last_name)])],
            'telecom' => array_values(array_filter([$m->phone ? ['system' => 'phone', 'value' => $m->phone, 'use' => 'mobile'] : null, $m->email ? ['system' => 'email', 'value' => $m->email] : null])),
            'gender' => $gender,
            'birthDate' => $m->date_of_birth?->format('Y-m-d'),
            'deceasedDateTime' => $this->time($m->deceased_at),
            'deceasedBoolean' => $m->deceased_at ? null : false,
            'address' => PatientAddress::query()->where('patient_id', $m->id)->get()->map(fn ($a) => array_filter(['use' => in_array($a->address_type, ['permanent', 'present'], true) ? 'home' : ($a->address_type === 'work' ? 'work' : 'temp'),
                'line' => array_values(array_filter([$a->line1, $a->line2])), 'city' => $a->city, 'district' => $a->district, 'postalCode' => $a->postal_code]))->all() ?: ($m->address ? [['text' => $m->address, 'city' => $m->city]] : []),
            'managingOrganization' => $this->ref('Organization', Organization::class, $m->organization_id),
        ], fn ($v) => $v !== null && $v !== []);
    }

    public function patientIdOf(Model $m): ?int
    {
        return (int) $m->getKey();
    }
}
