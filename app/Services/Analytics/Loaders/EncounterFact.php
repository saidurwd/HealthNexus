<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EncounterFact extends Loader
{
    public function key(): string
    {
        return 'fact_encounters';
    }

    public function label(): string
    {
        return 'Encounters';
    }

    protected function target(): string
    {
        return 'fact_encounters';
    }

    protected function uniqueBy(): array
    {
        return ['encounter_id'];
    }

    protected function source(): Builder
    {
        return DB::table('encounters')->select('encounters.id', 'encounters.organization_id', 'encounters.branch_id', 'encounters.patient_id', 'encounters.department_id', 'encounters.provider_id', 'encounters.encounter_type', 'encounters.status', 'encounters.priority', 'encounters.source', 'encounters.encounter_date', 'encounters.started_at', 'encounters.ended_at', 'encounters.completed_at', 'encounters.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'encounters.updated_at';
    }

    protected function idColumn(): string
    {
        return 'encounters.id';
    }

    protected function map(Collection $rows): array
    {
        $keys = $this->patientKeys($rows, fn ($r) => $this->day($r->encounter_date));

        return $rows->values()->map(fn ($r, $i) => ['encounter_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->day($r->encounter_date) ?? $this->localDay($r->started_at) ?? '19700101'),
            'department_id' => $r->department_id, 'provider_id' => $r->provider_id, 'patient_key' => $keys[$i], 'encounter_type' => $r->encounter_type, 'status' => $r->status, 'priority' => $r->priority, 'source' => $r->source,
            'duration_minutes' => $this->minutes($r->started_at, $r->completed_at ?? $r->ended_at), 'hour' => $this->localHour($r->started_at), 'completed' => in_array($r->status, ['completed', 'locked'], true)])->all();
    }
}
