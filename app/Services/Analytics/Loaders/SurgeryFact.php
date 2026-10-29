<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Operations: planned and actual duration, how late they started, and how many were cancelled. */
class SurgeryFact extends Loader
{
    public function key(): string
    {
        return 'fact_surgeries';
    }

    public function label(): string
    {
        return 'Operations';
    }

    protected function target(): string
    {
        return 'fact_surgeries';
    }

    protected function uniqueBy(): array
    {
        return ['surgery_id'];
    }

    protected function source(): Builder
    {
        return DB::table('ot_surgeries')->select('ot_surgeries.id', 'ot_surgeries.organization_id', 'ot_surgeries.branch_id', 'ot_surgeries.theatre_id', 'ot_surgeries.specialty_id', 'ot_surgeries.priority', 'ot_surgeries.surgery_type', 'ot_surgeries.status', 'ot_surgeries.scheduled_date',
            'ot_surgeries.scheduled_start_at', 'ot_surgeries.scheduled_end_at', 'ot_surgeries.actual_start_at', 'ot_surgeries.actual_end_at', 'ot_surgeries.created_at', 'ot_surgeries.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'ot_surgeries.updated_at';
    }

    protected function idColumn(): string
    {
        return 'ot_surgeries.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['surgery_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->day($r->scheduled_date) ?? $this->localDay($r->created_at) ?? '19700101'),
            'theatre_id' => $r->theatre_id, 'specialty_id' => $r->specialty_id, 'priority' => $r->priority, 'surgery_type' => $r->surgery_type, 'status' => $r->status, 'planned_minutes' => $this->minutes($r->scheduled_start_at, $r->scheduled_end_at),
            'actual_minutes' => $this->minutes($r->actual_start_at, $r->actual_end_at), 'start_delay_minutes' => $this->minutes($r->scheduled_start_at, $r->actual_start_at) ?? ($r->actual_start_at && $r->scheduled_start_at ? 0 : null),
            'completed' => $r->status === 'completed', 'cancelled' => $r->status === 'cancelled'])->all();
    }
}
