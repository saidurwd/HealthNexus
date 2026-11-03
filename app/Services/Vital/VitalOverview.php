<?php

namespace App\Services\Vital;

use App\Support\Dashboard\DashboardPeriod;
use App\Support\Dashboard\Days;
use App\Support\Dashboard\Spread;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the Births, deaths and police cases page. Now: deaths with no certificate yet (and how long the oldest has waited), certificates signed but not given to the family, those held back by a police case, police cases
 * the police have not been told about in time, births issued but not registered after the follow-up period. In the period (and the one before): births, stillbirths, deaths certified, and the hours from death to certificate.
 * Totals only: no patient is named.
 */
class VitalOverview
{
    public function __construct(private readonly DeathService $deaths, private readonly MedicoLegalService $medicoLegal, private readonly BirthService $births) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, DashboardPeriod $period, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $births = fn () => DB::table('vr_birth_notifications')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', '!=', 'cancelled');
        $certs = fn () => DB::table('vr_death_certifications')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', '!=', 'cancelled');
        $in = function (DashboardPeriod $p) use ($births, $certs) {
            $hours = $certs()->whereNotNull('certified_at')->whereBetween('died_at', [$p->fromUtc(), $p->toUtc()])->get(['died_at', 'certified_at'])->map(fn ($c) => intdiv(Spread::minutesBetween($c->died_at, $c->certified_at), 60));

            return [
                'births' => (int) $births()->where('outcome', 'live_birth')->whereBetween('born_at', [$p->fromUtc(), $p->toUtc()])->count(),
                'stillbirths' => (int) $births()->where('outcome', 'stillbirth')->whereBetween('born_at', [$p->fromUtc(), $p->toUtc()])->count(),
                'deaths' => (int) $certs()->whereBetween('died_at', [$p->fromUtc(), $p->toUtc()])->count(),
                'hours_to_certificate' => Spread::of($hours),
            ];
        };
        $awaiting = $this->deaths->awaiting($organizationId);
        $oldest = $awaiting->min('died_at');
        $manners = $certs()->whereBetween('died_at', [$period->fromUtc(), $period->toUtc()])->selectRaw('manner, count(*) as n')->groupBy('manner')->pluck('n', 'manner');

        return [
            'awaiting_certificate' => $awaiting->count(), 'oldest_awaiting_hours' => $oldest ? intdiv(Spread::minutesBetween($oldest, $now), 60) : null,
            'certified_not_issued' => (int) $certs()->where('status', 'certified')->count(),
            'held_by_police' => (int) $certs()->where('status', 'certified')->whereNotNull('medicolegal_case_id')->whereIn('medicolegal_case_id', DB::table('vr_medicolegal_cases')->where('status', '!=', 'closed')->select('id'))->count(),
            'police_cases_open' => (int) DB::table('vr_medicolegal_cases')->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->where('status', '!=', 'closed')->count(),
            'police_not_told_in_time' => $this->medicoLegal->notInformedInTime($organizationId)->count(),
            'births_to_follow_up' => $this->births->overdueForRegistration($organizationId)->count(),
            'births_waiting_for_issue' => (int) $births()->where('status', 'draft')->count(),
            'current' => $in($period), 'previous' => $in($period->previous()),
            'by_manner' => collect(config('vital.manners'))->map(fn ($m) => ['manner' => ucfirst(str_replace('_', ' ', $m)), 'deaths' => (int) ($manners[$m] ?? 0)])->filter(fn ($r) => $r['deaths'] > 0)->values()->all(),
            'per_day' => $this->perDay($births, $certs, $period),
        ];
    }

    /** @return list<array{day:string,births:int,deaths:int}> */
    private function perDay(\Closure $births, \Closure $certs, DashboardPeriod $period): array
    {
        $b = Days::perDay($births()->where('outcome', 'live_birth'), 'born_at', $period->from, $period->to);
        $d = Days::perDay($certs(), 'died_at', $period->from, $period->to);

        return collect($b)->map(fn ($n, $day) => ['day' => CarbonImmutable::parse($day)->format('d M'), 'births' => (int) $n, 'deaths' => (int) ($d[$day] ?? 0)])->values()->all();
    }
}
