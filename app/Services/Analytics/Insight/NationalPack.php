<?php

namespace App\Services\Analytics\Insight;

use App\Services\Analytics\Warehouse;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;

/**
 * The national reporting pack: monthly HMIS-style aggregates, and an export shaped as a DHIS2 `dataValueSet`. The figures come from the warehouse. The DHIS2 data-element ids, the organisation unit and
 * the data set are settings that stay empty until the hospital is onboarded with the national system; until then the export lists what is not yet mapped instead of guessing (verify requirements, P-18).
 */
class NationalPack
{
    /** @var array<string,string> key => label */
    public const INDICATORS = [
        'opd_attendance' => 'Outpatient attendance',
        'opd_attendance_under5' => 'Outpatient attendance, children under 5',
        'ipd_admissions' => 'Inpatient admissions',
        'ipd_discharges' => 'Inpatient discharges',
        'ipd_bed_days_occupied' => 'Occupied bed-days',
        'ipd_bed_days_available' => 'Available bed-days',
        'ipd_bed_occupancy_pct' => 'Bed occupancy (%)',
        'ipd_avg_length_of_stay' => 'Average length of stay (days)',
        'ed_visits' => 'Emergency visits',
        'surgeries_done' => 'Operations done',
        'lab_tests' => 'Laboratory tests',
        'radiology_exams' => 'Imaging examinations',
        'blood_units_issued' => 'Blood units issued',
    ];

    public function __construct(private readonly Warehouse $warehouse, private readonly SettingsService $settings) {}

    /** @return array<string, float|int|null> */
    public function compute(int $organizationId, string $month): array
    {
        $from = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $lo = (int) $from->format('Ymd');
        $hi = (int) $from->endOfMonth()->format('Ymd');
        $w = $this->warehouse;
        $in = fn (string $t) => $w->table("$t as f")->where('f.organization_id', $organizationId)->whereBetween('f.date_key', [$lo, $hi]);
        $opd = fn () => $in('fact_encounters')->whereRaw("lower(f.encounter_type) in ('opd', 'outpatient')");
        $occupied = (int) $in('fact_bed_census')->sum('beds_occupied');
        $available = (int) $in('fact_bed_census')->sum('beds_total');
        $metric = fn (string $m) => (int) $w->table('fact_daily_metrics as f')->where('f.organization_id', $organizationId)->whereBetween('f.date_key', [$lo, $hi])->where('f.metric', $m)->sum('value');
        $los = $in('fact_admissions')->where('f.is_discharged', true)->avg('f.los_days');

        return [
            'opd_attendance' => $opd()->count(),
            'opd_attendance_under5' => $opd()->join('dim_patient as p', 'p.patient_key', '=', 'f.patient_key')->whereIn('p.age_band', ['<1', '1-4'])->count(),
            'ipd_admissions' => $in('fact_admissions')->count(),
            'ipd_discharges' => $in('fact_admissions')->where('f.is_discharged', true)->count(),
            'ipd_bed_days_occupied' => $occupied,
            'ipd_bed_days_available' => $available,
            'ipd_bed_occupancy_pct' => $available > 0 ? round($occupied * 100 / $available, 1) : null,
            'ipd_avg_length_of_stay' => $los === null ? null : round((float) $los, 1),
            'ed_visits' => $in('fact_ed_visits')->count(),
            'surgeries_done' => $in('fact_surgeries')->where('f.completed', true)->count(),
            'lab_tests' => (int) $in('fact_lab_orders')->sum('f.items'),
            'radiology_exams' => $in('fact_radiology_exams')->count(),
            'blood_units_issued' => $metric('blood_units_issued'),
        ];
    }

    /** @return array{elements:array<string,string>,org_unit:?string,data_set:?string} */
    public function mapping(): array
    {
        $elements = $this->settings->get('analytics.national.elements');
        $elements = is_array($elements) && $elements !== [] ? $elements : (array) config('analytics.national.elements', []);

        return ['elements' => $elements, 'org_unit' => ($this->settings->get('analytics.national.org_unit') ?: config('analytics.national.org_unit')) ?: null, 'data_set' => ($this->settings->get('analytics.national.data_set') ?: config('analytics.national.data_set')) ?: null];
    }

    /** @return array{ready:bool,unmapped:list<string>,payload:array<string,mixed>} */
    public function dhis2(int $organizationId, string $month): array
    {
        $map = $this->mapping();
        $values = $this->compute($organizationId, $month);
        $dataValues = [];
        $unmapped = [];
        foreach (self::INDICATORS as $key => $label) {
            if (empty($map['elements'][$key])) {
                $unmapped[] = $key;

                continue;
            }
            $values[$key] !== null && $dataValues[] = ['dataElement' => $map['elements'][$key], 'value' => (string) $values[$key]];
        }
        $ready = $map['org_unit'] !== null && $map['data_set'] !== null && $unmapped === [];

        return ['ready' => $ready, 'unmapped' => array_merge($map['org_unit'] === null ? ['org_unit'] : [], $map['data_set'] === null ? ['data_set'] : [], $unmapped),
            'payload' => ['dataSet' => $map['data_set'], 'completeDate' => now()->toDateString(), 'period' => str_replace('-', '', $month), 'orgUnit' => $map['org_unit'], 'dataValues' => $dataValues]];
    }
}
