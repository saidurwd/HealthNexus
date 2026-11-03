<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Inpatient admissions and their length of stay. */
class AdmissionFact extends Loader
{
    public function key(): string
    {
        return 'fact_admissions';
    }

    public function label(): string
    {
        return 'Inpatient admissions';
    }

    protected function target(): string
    {
        return 'fact_admissions';
    }

    protected function uniqueBy(): array
    {
        return ['admission_id'];
    }

    protected function source(): Builder
    {
        return DB::table('ipd_admissions')->select('ipd_admissions.id', 'ipd_admissions.organization_id', 'ipd_admissions.branch_id', 'ipd_admissions.patient_id', 'ipd_admissions.department_id', 'ipd_admissions.attending_provider_id', 'ipd_admissions.admitted_at',
            'ipd_admissions.actual_discharge_date', 'ipd_admissions.status', 'ipd_admissions.discharge_disposition_id', 'ipd_admissions.created_at', 'ipd_admissions.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'ipd_admissions.updated_at';
    }

    protected function idColumn(): string
    {
        return 'ipd_admissions.id';
    }

    protected function map(Collection $rows): array
    {
        $in = fn ($r) => $this->localDay($r->admitted_at ?? $r->created_at);
        $keys = $this->patientKeys($rows, $in);

        return $rows->values()->map(function ($r, $i) use ($in, $keys) {
            $out = $this->day($r->actual_discharge_date);
            $los = $out ? max(1, (int) CarbonImmutable::parse($in($r))->diffInDays(CarbonImmutable::parse($out), false)) : null;

            return ['admission_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $in($r) ?? '19700101'), 'discharge_date_key' => $out ? (int) str_replace('-', '', $out) : null,
                'department_id' => $r->department_id, 'provider_id' => $r->attending_provider_id, 'patient_key' => $keys[$i], 'status' => $r->status, 'disposition_id' => $r->discharge_disposition_id, 'los_days' => $los, 'is_discharged' => $out !== null];
        })->all();
    }
}
