<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentFact extends Loader
{
    public function key(): string
    {
        return 'fact_appointments';
    }

    public function label(): string
    {
        return 'Appointments';
    }

    protected function target(): string
    {
        return 'fact_appointments';
    }

    protected function uniqueBy(): array
    {
        return ['appointment_id'];
    }

    protected function source(): Builder
    {
        return DB::table('appointments')->select('appointments.id', 'appointments.organization_id', 'appointments.branch_id', 'appointments.patient_id', 'appointments.provider_id', 'appointments.appointment_date', 'appointments.type', 'appointments.source', 'appointments.status', 'appointments.booked_at', 'appointments.checked_in_at', 'appointments.started_at', 'appointments.is_walk_in', 'appointments.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'appointments.updated_at';
    }

    protected function idColumn(): string
    {
        return 'appointments.id';
    }

    protected function map(Collection $rows): array
    {
        $keys = $this->patientKeys($rows, fn ($r) => $this->day($r->appointment_date));

        return $rows->values()->map(fn ($r, $i) => ['appointment_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->day($r->appointment_date)),
            'provider_id' => $r->provider_id, 'patient_key' => $keys[$i], 'type' => $r->type, 'source' => $r->source, 'status' => $r->status,
            'lead_days' => $r->booked_at ? (int) CarbonImmutable::parse($this->localDay($r->booked_at))->diffInDays(CarbonImmutable::parse($this->day($r->appointment_date)), false) : null,
            'wait_minutes' => $this->minutes($r->checked_in_at, $r->started_at), 'is_no_show' => $r->status === 'no_show', 'is_cancelled' => $r->status === 'cancelled', 'is_walk_in' => (bool) $r->is_walk_in, 'is_completed' => $r->status === 'completed'])->all();
    }
}
