<?php

namespace App\Interop\Mappers;

use App\Models\Branch;
use App\Models\Insurance\InsurancePayer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

/** The hospital as one Organization and each branch as an Organization that is part of it. */
class OrganizationMapper extends Mapper
{
    public function type(): string
    {
        return 'Organization';
    }

    public function sources(): array
    {
        return [
            ['model' => Organization::class, 'entity' => 'Organization', 'search' => ['name' => fn ($q, $v) => $q->where('name', 'like', '%'.addcslashes($v, '%_\\').'%')], 'scope' => 'id'],
            ['model' => Branch::class, 'entity' => 'Branch', 'search' => ['name' => fn ($q, $v) => $q->where('name', 'like', '%'.addcslashes($v, '%_\\').'%')]],
            ['model' => InsurancePayer::class, 'entity' => 'InsurancePayer', 'search' => ['name' => fn ($q, $v) => $q->where('name', 'like', '%'.addcslashes($v, '%_\\').'%')]],
        ];
    }

    /** @param Organization|Branch|InsurancePayer $m */
    public function toFhir(Model $m): array
    {
        $isBranch = $m instanceof Branch;

        return array_filter($this->base($m, $isBranch ? 'Branch' : ($m instanceof InsurancePayer ? 'InsurancePayer' : 'Organization')) + [
            'identifier' => $m->code ? [['system' => 'urn:healthnexus:org', 'value' => $m->code]] : [],
            'active' => (bool) ($m->is_active ?? true),
            'type' => [['coding' => [$m instanceof InsurancePayer ? $this->coding('http://terminology.hl7.org/CodeSystem/organization-type', 'pay', 'Payer') : $this->coding('http://terminology.hl7.org/CodeSystem/organization-type', 'prov', 'Healthcare Provider')]]],
            'name' => $m->name,
            'telecom' => array_values(array_filter([$m->phone ? ['system' => 'phone', 'value' => $m->phone] : null, $m->email ? ['system' => 'email', 'value' => $m->email] : null])),
            'partOf' => $isBranch ? $this->ref('Organization', Organization::class, $m->organization_id) : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    public function patientIdOf(Model $m): ?int
    {
        return null;
    }
}
