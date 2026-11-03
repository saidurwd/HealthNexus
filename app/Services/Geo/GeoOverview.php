<?php

namespace App\Services\Geo;

use App\Models\Geo\GeoDistrict;
use App\Models\Geo\GeoDivision;
use App\Models\Geo\GeoUnion;
use App\Models\Geo\GeoUpazila;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the Geography page: how complete the list of places is, and where the hospital's patients come from. "Come from" is the permanent address, the home district of the patient; a patient with two permanent addresses
 * is counted under both. Totals only: no patient is named.
 */
class GeoOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId): array
    {
        $permanent = fn () => DB::table('patient_addresses')->where('organization_id', $organizationId)->where('address_type', 'permanent')->whereNull('deleted_at');
        $patients = (int) DB::table('patients')->where('organization_id', $organizationId)->whereNull('deleted_at')->count();
        $withPlace = (int) $permanent()->whereNotNull('district_id')->distinct()->count('patient_id');
        $byDistrict = $permanent()->join('geo_districts as d', 'd.id', '=', 'patient_addresses.district_id')->selectRaw('d.name_en as district, count(distinct patient_addresses.patient_id) as patients')->groupBy('d.id', 'd.name_en')->orderByDesc('patients')->orderBy('d.name_en')->limit(15)->get();
        $byDivision = $permanent()->join('geo_divisions as v', 'v.id', '=', 'patient_addresses.division_id')->selectRaw('v.name_en as division, count(distinct patient_addresses.patient_id) as patients')->groupBy('v.id', 'v.name_en')->orderByDesc('patients')->get();
        $served = (int) $permanent()->whereNotNull('district_id')->distinct()->count('district_id');
        $withoutUnions = (int) GeoUpazila::query()->whereDoesntHave('unions')->whereIn('id', $permanent()->whereNotNull('upazila_id')->select('upazila_id'))->count();

        return [
            'divisions' => GeoDivision::query()->count(), 'districts' => GeoDistrict::query()->count(), 'upazilas' => GeoUpazila::query()->count(), 'unions' => GeoUnion::query()->count(),
            'patients' => $patients, 'with_place' => $withPlace, 'with_place_percent' => $patients > 0 ? round(100 * $withPlace / $patients, 1) : null,
            'districts_served' => $served, 'upazilas_without_unions' => $withoutUnions,
            'by_district' => $byDistrict->map(fn ($r) => ['district' => $r->district, 'patients' => (int) $r->patients])->all(),
            'by_division' => $byDivision->map(fn ($r) => ['division' => $r->division, 'patients' => (int) $r->patients])->all(),
        ];
    }
}
