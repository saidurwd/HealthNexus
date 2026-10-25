<?php

namespace App\Services\Clinical\Observations;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read model for measurements (guideline 10.4, H-03 step 1): one query over the new store AND the legacy
 * tables (OPD vitals, nursing and ICU observations), so trends and "latest vitals" already show every
 * location. Nothing is copied or moved; the legacy tables stay the writers until their modules switch over.
 *
 * Rows: source_module, source_record_id, value (float|string), unit_code, effective_at, status.
 */
class ObservationReader
{
    /** encounter_vitals / vital_signs wide column => catalogue key */
    private const WIDE_COLUMNS = [
        'temperature' => 'body_temperature', 'pulse_rate' => 'heart_rate', 'respiratory_rate' => 'respiratory_rate',
        'oxygen_saturation' => 'oxygen_saturation', 'systolic' => 'systolic_bp', 'diastolic' => 'diastolic_bp',
        'weight' => 'body_weight', 'height' => 'body_height', 'bmi' => 'bmi',
    ];

    /** @return Collection<int, object> oldest first */
    public function series(int $organizationId, int $patientId, string $key, ?CarbonInterface $from = null, ?CarbonInterface $to = null, int $limit = 500): Collection
    {
        $entry = config("observations.vitals.$key") ?? throw new \InvalidArgumentException("Unknown observation [$key].");

        $union = $this->current($organizationId, $patientId, $entry['code']);
        foreach ($this->legacy($organizationId, $patientId, $key, $entry) as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, 'obs')
            ->when($from, fn ($q) => $q->where('effective_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('effective_at', '<=', $to))
            ->orderByDesc('effective_at')->limit($limit)->get()
            ->filter(fn ($r) => is_numeric($r->value) || $r->value_text !== null)
            ->map(function ($r) {
                $r->value = is_numeric($r->value) ? (float) $r->value : $r->value_text;
                $r->effective_at = Carbon::parse($r->effective_at, 'UTC');

                return $r;
            })
            ->sortBy('effective_at')->values();
    }

    public function latest(int $organizationId, int $patientId, string $key): ?object
    {
        return $this->series($organizationId, $patientId, $key, limit: 50)->last();
    }

    /** @return array<string, ?object> latest value of every catalogue vital */
    public function latestVitals(int $organizationId, int $patientId): array
    {
        return collect(array_keys(config('observations.vitals')))
            ->mapWithKeys(fn ($key) => [$key => $this->latest($organizationId, $patientId, $key)])
            ->all();
    }

    private function current(int $organizationId, int $patientId, string $code): Builder
    {
        return DB::table('clinical_observations as o')
            ->where('o.organization_id', $organizationId)->where('o.patient_id', $patientId)->where('o.code', $code)
            ->where('o.status', '!=', 'entered_in_error')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('clinical_observations as n')->whereColumn('n.supersedes_id', 'o.id'))
            ->selectRaw('o.source_module as source_module, cast(o.id as char) as source_record_id, o.value_number as value, o.value_text as value_text, o.unit_code as unit_code, o.effective_at as effective_at, o.status as status');
    }

    /** @return list<Builder> */
    private function legacy(int $organizationId, int $patientId, string $key, array $entry): array
    {
        $queries = [];
        $unit = $entry['unit'];

        foreach (['encounter_vitals' => 'encounter_vitals', 'vital_signs' => 'vital_signs'] as $table) {
            $column = array_search($key, self::WIDE_COLUMNS, true);
            if ($column === false) {
                continue;
            }

            $q = DB::table("$table as v")->where('v.patient_id', $patientId)->whereNotNull("v.$column");
            $table === 'encounter_vitals'
                ? $q->join('encounters as e', 'e.id', '=', 'v.encounter_id')->where('e.organization_id', $organizationId)
                : $q->where('v.organization_id', $organizationId)->whereNull('v.deleted_at');

            $queries[] = $q->selectRaw("'$table' as source_module, cast(v.id as char) as source_record_id, v.$column as value, null as value_text, '$unit' as unit_code, coalesce(v.recorded_at, v.created_at) as effective_at, 'final' as status");
        }

        $aliases = $entry['aliases'];
        $queries[] = DB::table('nursing_observations as n')
            ->where('n.organization_id', $organizationId)->where('n.patient_id', $patientId)
            ->whereIn('n.observation_type', $aliases)->where('n.status', '!=', 'cancelled')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('nursing_observations as c')->whereColumn('c.corrects_observation_id', 'n.id'))
            ->selectRaw("'nursing_observations' as source_module, cast(n.id as char) as source_record_id, n.value as value, null as value_text, coalesce(n.unit, '$unit') as unit_code, n.observed_at as effective_at, n.status as status");

        $queries[] = DB::table('icu_observations as i')
            ->where('i.organization_id', $organizationId)->where('i.patient_id', $patientId)
            ->where(fn ($q) => $q->whereIn('i.observation_type', $aliases)->orWhere('i.code', $entry['code']))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('icu_observations as c')->whereColumn('c.corrects_observation_id', 'i.id'))
            ->selectRaw("'icu_observations' as source_module, cast(i.id as char) as source_record_id, i.value_numeric as value, i.value_text as value_text, coalesce(i.unit, '$unit') as unit_code, i.observed_at as effective_at, 'final' as status");

        return $queries;
    }
}
