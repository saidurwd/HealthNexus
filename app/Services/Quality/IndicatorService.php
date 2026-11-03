<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAction;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIndicator;
use App\Models\Quality\QaIndicatorValue;
use App\Models\Quality\QaInfectionCase;
use App\Models\Quality\QaMortalityReview;
use App\Services\Analytics\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Quality indicators, one value per hospital per month. Volumes come from the Phase 19 warehouse (admissions, bed-days, dispensings); events come from this module's own tables.
 * A value is a numerator over a denominator, kept with both so a reader can see how it was made; a month with no denominator has no value (never zero).
 */
class IndicatorService
{
    /** code => [name, description, domain, unit, good direction, target] — the formula key is the code. */
    public const STANDARD = [
        'readmission_30d' => ['Readmission within 30 days', 'Discharged patients who came back as inpatients within 30 days, out of discharges.', 'outcome', 'percent', 'down', 8],
        'repeat_operation_30d' => ['Repeat operation within 30 days', 'Operations followed by another operation on the same patient within 30 days, out of completed operations. Planned staged operations are included: read the cases before judging.', 'outcome', 'percent', 'down', 3],
        'inpatient_mortality' => ['Inpatient mortality', 'Deaths in the wards out of patients leaving the wards (discharged or died).', 'outcome', 'percent', 'down', null],
        'mortality_review_on_time' => ['Deaths reviewed in time', 'Deaths whose review was completed within the set number of days, out of deaths.', 'process', 'percent', 'up', 90],
        'falls_per_1000' => ['Falls per 1,000 bed-days', 'Reported falls out of occupied bed-days.', 'safety', 'per_1000', 'down', 3],
        'pressure_injury_per_1000' => ['Pressure injuries per 1,000 bed-days', 'Reported pressure injuries out of occupied bed-days.', 'safety', 'per_1000', 'down', 1],
        'medication_events_per_1000' => ['Medication events per 1,000 dispensings', 'Reported medication incidents and near misses out of dispensings.', 'safety', 'per_1000', 'down', null],
        'hai_per_1000' => ['Hospital-acquired infections per 1,000 bed-days', 'Confirmed infections that began on or after the set hospital day, out of occupied bed-days.', 'infection', 'per_1000', 'down', 4],
        'hand_hygiene' => ['Hand-hygiene compliance', 'Moments observed where hands were cleaned correctly, out of moments observed.', 'infection', 'percent', 'up', 80],
        'incident_reports_per_1000' => ['Incident reports per 1,000 bed-days', 'All reports (including near misses) out of occupied bed-days. A higher number usually means people feel safe to report.', 'process', 'per_1000', 'up', null],
        'near_miss_share' => ['Near misses among reports', 'Near misses out of all reports. A healthy reporting culture reports many near misses.', 'process', 'percent', 'up', 30],
        'incidents_closed_on_time' => ['Incidents closed on time', 'Incidents closed by their due date, out of incidents closed.', 'process', 'percent', 'up', 90],
        'capa_on_time' => ['Actions finished on time', 'Actions finished by their due date, out of actions finished.', 'process', 'percent', 'up', 90],
    ];

    public function __construct(private readonly Warehouse $warehouse, private readonly QualityPolicy $policy) {}

    public function install(): int
    {
        $n = 0;
        foreach (self::STANDARD as $code => [$name, $description, $domain, $unit, $direction, $target]) {
            $i = QaIndicator::query()->firstOrCreate(['organization_id' => null, 'code' => $code], ['name' => $name, 'description' => $description, 'domain' => $domain, 'unit' => $unit, 'good_direction' => $direction, 'formula' => $code, 'target' => $target, 'active' => true]);
            $i->wasRecentlyCreated && $n++;
        }

        return $n;
    }

    /** The indicators a hospital uses: the standard ones, each replaced by the hospital's own copy where it set a target. @return \Illuminate\Support\Collection<int,QaIndicator> */
    public function forOrganization(int $organizationId): Collection
    {
        return QaIndicator::query()->where('active', true)->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organizationId))->orderBy('domain')->orderBy('name')->get()
            ->sortByDesc(fn ($i) => $i->organization_id !== null)->unique('code')->sortBy([['domain', 'asc'], ['name', 'asc']])->values();
    }

    /** The hospital's own target for an indicator (the standard one is never changed): a copy is made on the first change. */
    public function setTarget(int $organizationId, QaIndicator $indicator, ?float $target): QaIndicator
    {
        if ($indicator->organization_id !== null && $indicator->organization_id !== $organizationId) {
            abort(404);
        }
        if ($indicator->organization_id === null) {
            $indicator = QaIndicator::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $indicator->code], ['name' => $indicator->name, 'description' => $indicator->description, 'domain' => $indicator->domain, 'unit' => $indicator->unit,
                'good_direction' => $indicator->good_direction, 'formula' => $indicator->formula, 'target' => $target, 'active' => true]);
        }
        $indicator->update(['target' => $target]);

        return $indicator;
    }

    /** Compute every active indicator for each of the last N months (the current month included). @return int values written */
    public function computeRecent(int $organizationId, ?int $months = null): int
    {
        $months ??= $this->policy->int('indicator.months_back', 12);
        $written = 0;
        $tz = (string) config('quality.timezone', 'Asia/Dhaka');
        for ($i = 0; $i < $months; $i++) {
            $written += $this->computeMonth($organizationId, CarbonImmutable::now($tz)->startOfMonth()->subMonths($i));
        }

        return $written;
    }

    public function computeMonth(int $organizationId, CarbonImmutable $month): int
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();
        $bedDays = (float) $this->warehouse->table('fact_bed_census')->where('organization_id', $organizationId)->whereBetween('date_key', [(int) $from->format('Ymd'), (int) $to->format('Ymd')])->sum('beds_occupied');
        $n = 0;
        foreach ($this->forOrganization($organizationId) as $indicator) {
            [$num, $den] = $this->measure($indicator->formula, $organizationId, $from, $to, $bedDays);
            $scale = $indicator->unit === 'per_1000' ? 1000 : ($indicator->unit === 'percent' ? 100 : 1);
            $value = $den > 0 ? round($scale * $num / $den, 4) : null;
            QaIndicatorValue::query()->updateOrCreate(['organization_id' => $organizationId, 'indicator_id' => $indicator->id, 'period' => $from->toDateString()],
                ['numerator' => $num, 'denominator' => $den, 'value' => $value, 'computed_at' => now()]);
            $n++;
        }

        return $n;
    }

    /** @return array{0:float,1:float} numerator and denominator */
    private function measure(string $formula, int $org, CarbonImmutable $from, CarbonImmutable $to, float $bedDays): array
    {
        $fk = (int) $from->format('Ymd');
        $tk = (int) $to->format('Ymd');
        $utcFrom = $from->setTimezone('UTC');
        $utcTo = $to->setTimezone('UTC');
        $incidents = fn () => QaIncident::query()->with('amendments')->where('organization_id', $org)->whereBetween('occurred_at', [$from->startOfDay()->utc(), $to->endOfDay()->utc()])->get();

        return match ($formula) {
            'readmission_30d' => $this->readmissions($org, $fk, $tk),
            'repeat_operation_30d' => $this->repeatOperations($org, $from->startOfDay()->utc(), $to->endOfDay()->utc()),
            'inpatient_mortality' => $this->inpatientMortality($org, $fk, $tk),
            'mortality_review_on_time' => $this->reviewsOnTime($org, $utcFrom, $utcTo),
            'falls_per_1000' => [$incidents()->filter(fn ($i) => $i->effective('category') === 'fall' && $i->kind !== 'near_miss')->count(), $bedDays],
            'pressure_injury_per_1000' => [$incidents()->filter(fn ($i) => $i->effective('category') === 'pressure_injury')->count(), $bedDays],
            'medication_events_per_1000' => [$incidents()->filter(fn ($i) => $i->effective('category') === 'medication')->count(), (float) $this->warehouse->table('fact_dispensings')->where('organization_id', $org)->whereBetween('date_key', [$fk, $tk])->count()],
            'hai_per_1000' => [QaInfectionCase::query()->where('organization_id', $org)->where('status', 'confirmed')->where('hospital_day', '>=', $this->policy->int('infection.hospital_acquired_from_day', 3))->whereBetween('onset_on', [$from->toDateString(), $to->toDateString()])->count(), $bedDays],
            'hand_hygiene' => $this->handHygiene($org, $from, $to),
            'incident_reports_per_1000' => [$incidents()->count(), $bedDays],
            'near_miss_share' => (function () use ($incidents) {
                $all = $incidents();

                return [$all->where('kind', 'near_miss')->count(), $all->count()];
            })(),
            'incidents_closed_on_time' => (function () use ($org, $utcFrom, $utcTo) {
                $closed = QaIncident::query()->where('organization_id', $org)->where('status', 'closed')->whereBetween('closed_at', [$utcFrom, $utcTo])->get(['due_on', 'closed_at']);

                return [$closed->filter(fn ($i) => $i->due_on === null || $i->closed_at->copy()->setTimezone(config('quality.timezone'))->toDateString() <= $i->due_on->toDateString())->count(), $closed->count()];
            })(),
            'capa_on_time' => (function () use ($org, $utcFrom, $utcTo) {
                $done = QaAction::query()->where('organization_id', $org)->whereIn('status', ['done', 'verified'])->whereBetween('done_at', [$utcFrom, $utcTo])->get(['due_on', 'done_at']);

                return [$done->filter(fn ($a) => $a->done_at->copy()->setTimezone(config('quality.timezone'))->toDateString() <= $a->due_on->toDateString())->count(), $done->count()];
            })(),
            default => [0.0, 0.0],
        };
    }

    /** @return array{0:float,1:float} */
    private function readmissions(int $org, int $fk, int $tk): array
    {
        $out = $this->warehouse->table('fact_admissions')->where('organization_id', $org)->where('is_discharged', true)->whereNotNull('patient_key')->where('status', '!=', 'deceased')->whereBetween('discharge_date_key', [$fk, $tk])->get(['admission_id', 'patient_key', 'discharge_date_key']);
        $back = 0;
        foreach ($out->pluck('patient_key')->unique()->chunk(500) as $chunk) {
            $later = $this->warehouse->table('fact_admissions')->where('organization_id', $org)->whereIn('patient_key', $chunk->all())->get(['admission_id', 'patient_key', 'date_key'])->groupBy('patient_key');
            foreach ($out->whereIn('patient_key', $chunk->all()) as $a) {
                $limit = (int) CarbonImmutable::createFromFormat('Ymd', (string) $a->discharge_date_key)->addDays($this->policy->int('indicator.readmission_days', 30))->format('Ymd');
                if (($later[$a->patient_key] ?? collect())->contains(fn ($x) => $x->admission_id !== $a->admission_id && $x->date_key > $a->discharge_date_key && $x->date_key <= $limit)) {
                    $back++;
                }
            }
        }

        return [$back, $out->count()];
    }

    /** @return array{0:float,1:float} */
    private function repeatOperations(int $org, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $done = ['procedure_completed', 'recovery', 'transferred', 'closed'];
        $ops = DB::table('ot_surgeries')->where('organization_id', $org)->whereIn('status', $done)->whereBetween('completed_at', [$from, $to])->get(['id', 'patient_id', 'completed_at']);
        $repeat = 0;
        foreach ($ops->pluck('patient_id')->unique()->chunk(500) as $chunk) {
            $all = DB::table('ot_surgeries')->where('organization_id', $org)->whereIn('status', $done)->whereIn('patient_id', $chunk->all())->get(['id', 'patient_id', 'completed_at'])->groupBy('patient_id');
            foreach ($ops->whereIn('patient_id', $chunk->all()) as $o) {
                $end = CarbonImmutable::parse($o->completed_at)->addDays(30);
                if ($all[$o->patient_id]->contains(fn ($x) => $x->id !== $o->id && $x->completed_at > $o->completed_at && CarbonImmutable::parse($x->completed_at)->lte($end))) {
                    $repeat++;
                }
            }
        }

        return [$repeat, $ops->count()];
    }

    /** @return array{0:float,1:float} */
    private function inpatientMortality(int $org, int $fk, int $tk): array
    {
        $left = $this->warehouse->table('fact_admissions')->where('organization_id', $org)->where(fn ($q) => $q->whereBetween('discharge_date_key', [$fk, $tk])->orWhere(fn ($w) => $w->whereNull('discharge_date_key')->where('status', 'deceased')->whereBetween('date_key', [$fk, $tk])))->count();
        $from = CarbonImmutable::createFromFormat('Ymd', (string) $fk)->startOfDay()->utc();
        $to = CarbonImmutable::createFromFormat('Ymd', (string) $tk)->endOfDay()->utc();
        $deaths = QaMortalityReview::query()->where('organization_id', $org)->where('source_type', 'ipd_admission')->whereBetween('died_at', [$from, $to])->count();

        return [$deaths, max($left, $deaths)];
    }

    /** @return array{0:float,1:float} */
    private function reviewsOnTime(int $org, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $deaths = QaMortalityReview::query()->where('organization_id', $org)->whereBetween('died_at', [$from, $to])->get(['died_at', 'reviewed_at', 'status']);
        $days = $this->policy->int('indicator.mortality_review_days', 30);
        $ok = $deaths->filter(fn ($d) => $d->reviewed_at !== null && $d->reviewed_at->lte($d->died_at->copy()->addDays($days)))->count();

        return [$ok, $deaths->count()];
    }

    /** @return array{0:float,1:float} */
    private function handHygiene(int $org, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $r = DB::table('qa_hand_hygiene_audits')->where('organization_id', $org)->whereBetween('audited_on', [$from->toDateString(), $to->toDateString()])->selectRaw('sum(moments_compliant) as c, sum(moments_observed) as o')->first();

        return [(float) ($r->c ?? 0), (float) ($r->o ?? 0)];
    }

    /** True when the value is on the good side of the target (null when there is no target or value). */
    public function onTarget(QaIndicator $indicator, ?float $value): ?bool
    {
        if ($value === null || $indicator->target === null) {
            return null;
        }
        $target = (float) $indicator->target;

        return $indicator->good_direction === 'down' ? $value <= $target : $value >= $target;
    }
}
