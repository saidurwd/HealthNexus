<?php

namespace App\Interop\Mappers;

use App\Models\Provider;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Model;

class PractitionerMapper extends Mapper
{
    public function type(): string
    {
        return 'Practitioner';
    }

    public function sources(): array
    {
        return [['model' => Provider::class, 'entity' => 'Provider', 'search' => [
            'name' => fn ($q, $v) => $q->where('name', 'like', '%'.addcslashes($v, '%_\\').'%'),
            'identifier' => fn ($q, $v) => $q->where('license_number', str_contains($v, '|') ? explode('|', $v, 2)[1] : $v),
            'active' => fn ($q, $v) => $q->where('status', $v === 'true' ? '=' : '!=', 'active'),
        ]]];
    }

    /** @param Provider $m */
    public function toFhir(Model $m): array
    {
        $specialty = $m->specialty_id ? Specialty::query()->whereKey($m->specialty_id)->value('name') : null;

        return array_filter($this->base($m, 'Provider') + [
            'identifier' => array_values(array_filter([$m->license_number ? ['system' => 'urn:bd:bmdc', 'value' => $m->license_number] : null, $m->provider_code ? ['system' => 'urn:healthnexus:provider', 'value' => $m->provider_code] : null])),
            'active' => $m->status === 'active',
            'name' => [['text' => $m->name]],
            'qualification' => $specialty ? [['code' => ['text' => $specialty]]] : null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    public function patientIdOf(Model $m): ?int
    {
        return null;
    }
}
