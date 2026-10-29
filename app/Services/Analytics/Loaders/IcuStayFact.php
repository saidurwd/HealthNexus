<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IcuStayFact extends Loader
{
    public function key(): string
    {
        return 'fact_icu_stays';
    }

    public function label(): string
    {
        return 'Intensive care stays';
    }

    protected function target(): string
    {
        return 'fact_icu_stays';
    }

    protected function uniqueBy(): array
    {
        return ['episode_id'];
    }

    protected function source(): Builder
    {
        return DB::table('icu_episodes')->select('icu_episodes.id', 'icu_episodes.organization_id', 'icu_episodes.branch_id', 'icu_episodes.patient_id', 'icu_episodes.status', 'icu_episodes.admitted_at', 'icu_episodes.discharged_at', 'icu_episodes.created_at', 'icu_episodes.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'icu_episodes.updated_at';
    }

    protected function idColumn(): string
    {
        return 'icu_episodes.id';
    }

    protected function map(Collection $rows): array
    {
        $when = fn ($r) => $this->localDay($r->admitted_at ?? $r->created_at);
        $keys = $this->patientKeys($rows, $when);

        return $rows->values()->map(fn ($r, $i) => ['episode_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $when($r) ?? '19700101'), 'patient_key' => $keys[$i], 'status' => $r->status,
            'los_hours' => $r->admitted_at && $r->discharged_at ? round(CarbonImmutable::parse($r->admitted_at)->diffInMinutes(CarbonImmutable::parse($r->discharged_at), false) / 60, 2) : null, 'discharged' => $r->discharged_at !== null])->all();
    }
}
