<?php

namespace App\Services\Watch;

use App\Models\Watch\SvCondition;
use App\Support\Dashboard\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the Disease watch page. Now: signals waiting for a look, being investigated, confirmed but not yet reported to the authority. In the period (and the one before): distinct patients with a watched condition, signals raised and
 * confirmed, and the cases of each condition and the districts they come from. Totals only: no patient is named.
 */
class WatchOverview
{
    public function __construct(private readonly SurveillanceService $surveillance) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $signals = fn () => DB::table('sv_signals')->where('organization_id', $organizationId);
        $conditions = SvCondition::query()->where(['organization_id' => $organizationId, 'active' => true])->orderBy('name')->get();
        $count = function (DashboardPeriod $p) use ($conditions) {
            $by = [];
            $patients = [];
            foreach ($conditions as $c) {
                $cases = $this->surveillance->cases($c, $p->from, $p->to);
                $by[$c->id] = $cases;
                foreach ($cases as $x) {
                    $patients[$x->patient_id] = true;
                }
            }

            return [$by, count($patients)];
        };
        [$cur, $curPatients] = $count($period);
        [, $prevPatients] = $count($period->previous());
        $districts = collect($cur)->flatten(1)->whereNotNull('district_id')->groupBy('district_id')->map->count()->sortDesc()->take(10);
        $names = DB::table('geo_districts')->whereIn('id', $districts->keys())->pluck('name_en', 'id');

        return [
            'new' => (int) $signals()->where('status', 'new')->count(), 'investigating' => (int) $signals()->where('status', 'investigating')->count(),
            'to_report' => (int) $signals()->where('status', 'confirmed')->whereNull('reported_at')->count(),
            'watched' => $conditions->count(),
            'current' => ['patients' => $curPatients, 'signals' => (int) $signals()->whereBetween('raised_at', [$period->fromUtc(), $period->toUtc()])->count(), 'confirmed' => (int) $signals()->where('status', 'confirmed')->whereBetween('decided_at', [$period->fromUtc(), $period->toUtc()])->count()],
            'previous' => ['patients' => $prevPatients],
            'by_condition' => $conditions->map(fn ($c) => ['condition' => $c->name, 'cases' => $cur[$c->id]->count()])->filter(fn ($r) => $r['cases'] > 0)->sortByDesc('cases')->values()->all(),
            'by_district' => $districts->map(fn ($n, $id) => ['district' => $names[$id] ?? '?', 'cases' => $n])->values()->all(),
            'no_district' => (int) collect($cur)->flatten(1)->whereNull('district_id')->count(),
        ];
    }
}
