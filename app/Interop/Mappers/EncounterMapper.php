<?php

namespace App\Interop\Mappers;

use App\Models\Branch;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class EncounterMapper extends Mapper
{
    public function type(): string
    {
        return 'Encounter';
    }

    public function sources(): array
    {
        return [['model' => Encounter::class, 'entity' => 'Encounter', 'patient' => 'patient_id', 'search' => [
            'date' => fn ($q, $v) => $q->whereDate('encounter_date', $v),
            'status' => fn ($q, $v) => $q->whereIn('status', match ($v) {
                'finished' => ['completed', 'locked'], 'in-progress' => ['in_progress', 'started'], 'cancelled' => ['cancelled'], 'planned' => ['planned', 'scheduled'], default => ['__none__']
            }),
        ]]];
    }

    /** @param Encounter $m */
    public function toFhir(Model $m): array
    {
        $type = strtolower((string) $m->encounter_type);
        [$class, $display] = match (true) {
            in_array($type, ['ipd', 'inpatient', 'icu'], true) => ['IMP', 'inpatient encounter'],
            in_array($type, ['ed', 'emergency'], true) => ['EMER', 'emergency'],
            default => ['AMB', 'ambulatory'],
        };
        $provider = $m->provider_id ? Provider::query()->where('user_id', $m->provider_id)->first() : null;

        return array_filter($this->base($m) + [
            'identifier' => [['system' => 'urn:healthnexus:encounter', 'value' => $m->encounter_no]],
            'status' => match ($m->status) {
                'completed', 'locked' => 'finished', 'cancelled' => 'cancelled', 'planned', 'scheduled' => 'planned', default => 'in-progress'
            },
            'class' => $this->coding('http://terminology.hl7.org/CodeSystem/v3-ActCode', $class, $display),
            'subject' => $this->ref('Patient', Patient::class, $m->patient_id),
            'participant' => $provider ? [['individual' => $this->ref('Practitioner', Provider::class, $provider->id, $provider->name, 'Provider')]] : null,
            'period' => array_filter(['start' => $this->time($m->started_at) ?? ($m->encounter_date ? $m->encounter_date->format('Y-m-d') : null), 'end' => $this->time($m->ended_at ?? $m->completed_at)]),
            'reasonCode' => $m->reason_for_visit || $m->chief_complaint_summary ? [['text' => $m->reason_for_visit ?: $m->chief_complaint_summary]] : null,
            'serviceProvider' => $this->ref('Organization', Branch::class, $m->branch_id, null, 'Branch'),
        ], fn ($v) => $v !== null && $v !== []);
    }
}
