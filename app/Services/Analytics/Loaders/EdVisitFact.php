<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Emergency visits: door to triage, door to doctor, total time, and how each ended. */
class EdVisitFact extends Loader
{
    public function key(): string
    {
        return 'fact_ed_visits';
    }

    public function label(): string
    {
        return 'Emergency visits';
    }

    protected function target(): string
    {
        return 'fact_ed_visits';
    }

    protected function uniqueBy(): array
    {
        return ['visit_id'];
    }

    protected function source(): Builder
    {
        return DB::table('ed_visits')->select('ed_visits.id', 'ed_visits.organization_id', 'ed_visits.branch_id', 'ed_visits.arrival_at', 'ed_visits.triage_level', 'ed_visits.status', 'ed_visits.triaged_at', 'ed_visits.doctor_seen_at', 'ed_visits.disposition_type', 'ed_visits.dispositioned_at', 'ed_visits.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'ed_visits.updated_at';
    }

    protected function idColumn(): string
    {
        return 'ed_visits.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['visit_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->localDay($r->arrival_at) ?? '19700101'), 'hour' => $this->localHour($r->arrival_at), 'triage_level' => $r->triage_level,
            'status' => $r->status, 'door_to_triage_min' => $this->minutes($r->arrival_at, $r->triaged_at), 'door_to_doctor_min' => $this->minutes($r->arrival_at, $r->doctor_seen_at), 'los_minutes' => $this->minutes($r->arrival_at, $r->dispositioned_at),
            'disposition_type' => $r->disposition_type, 'seen' => $r->doctor_seen_at !== null])->all();
    }
}
