<?php

namespace App\Services\Welfare;

use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use App\Models\Welfare\WfGrant;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Support\Facades\DB;

/** The figures on the welfare home page. */
class WelfareOverview
{
    public function __construct(private readonly FundService $funds) {}

    /** @return array<string,mixed> */
    public function summary(int $organizationId, int $days = 30): array
    {
        $from = today()->subDays($days - 1);
        $funds = WfFund::query()->where('organization_id', $organizationId)->orderBy('name')->get()->map(fn (WfFund $f) => ['fund' => $f, 'balance' => $this->funds->balance($f), 'committed' => $this->funds->committed($f), 'available' => $this->funds->available($f)]);
        $applied = WfApplication::query()->where('organization_id', $organizationId)->where('status', 'applied')->where('settled_at', '>=', $from);

        return [
            'funds' => $funds,
            'total_available' => number_format((float) $funds->sum(fn ($r) => (float) $r['available']), 2, '.', ''),
            'assessments_waiting' => WfAssessment::query()->where('organization_id', $organizationId)->where('status', 'submitted')->count(),
            'grants_waiting' => WfGrant::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'with_billing' => WfApplication::query()->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'given' => number_format((float) (clone $applied)->sum('amount'), 2, '.', ''),
            'patients_helped' => (clone $applied)->distinct()->count('patient_id'),
            'received' => number_format((float) WfFundEntry::query()->where('organization_id', $organizationId)->where('entry_type', 'donation')->where('occurred_at', '>=', $from)->sum('amount'), 2, '.', ''),
            'days' => $days,
        ];
    }

    /**
     * What the dashboard shows for a period, as plain arrays (it is cached). "Now" figures (free money, what waits) are live; "in the period" figures are given with the same figures for the period before.
     *
     * @return array<string,mixed>
     */
    public function summaryFor(int $organizationId, DashboardPeriod $period): array
    {
        $now = $this->summary($organizationId, 30);
        $in = fn (DashboardPeriod $p) => $this->inPeriod($organizationId, $p);
        $month = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', occurred_at)" : "date_format(occurred_at, '%Y-%m')";
        $from = $period->to->startOfMonth()->subMonths(5);
        $received = WfFundEntry::query()->where('organization_id', $organizationId)->where('entry_type', 'donation')->where('occurred_at', '>=', $from->utc())->selectRaw("$month as m, sum(amount) as total")->groupBy('m')->pluck('total', 'm');
        $given = WfFundEntry::query()->where('organization_id', $organizationId)->where('entry_type', 'disbursement')->where('occurred_at', '>=', $from->utc())->selectRaw("$month as m, sum(amount) as total")->groupBy('m')->pluck('total', 'm');
        $months = collect(range(0, 5))->map(function ($i) use ($from, $received, $given) {
            $m = $from->addMonths($i);

            return ['month' => $m->format('M Y'), 'received' => (float) ($received[$m->format('Y-m')] ?? 0), 'given' => -1 * (float) ($given[$m->format('Y-m')] ?? 0)];
        })->all();
        $byFund = DB::table('wf_fund_entries as e')->join('wf_funds as f', 'f.id', '=', 'e.fund_id')->where('e.organization_id', $organizationId)->where('e.entry_type', 'disbursement')->whereBetween('e.occurred_at', [$period->fromUtc(), $period->toUtc()])
            ->selectRaw('f.name as fund, -sum(e.amount) as given')->groupBy('f.id', 'f.name')->orderByDesc('given')->get()->map(fn ($r) => ['fund' => $r->fund, 'given' => (float) $r->given])->all();

        return [
            'funds' => $now['funds']->map(fn ($r) => ['name' => $r['fund']->name, 'kind' => $r['fund']->kind, 'status' => $r['fund']->status, 'public_id' => $r['fund']->public_id, 'balance' => $r['balance'], 'committed' => $r['committed'], 'available' => $r['available']])->all(),
            'total_available' => $now['total_available'], 'assessments_waiting' => $now['assessments_waiting'], 'grants_waiting' => $now['grants_waiting'], 'with_billing' => $now['with_billing'],
            'current' => $in($period), 'previous' => $in($period->previous()), 'months' => $months, 'by_fund' => $byFund,
        ];
    }

    /** @return array{given:float,discounts:int,patients:int,received:float,gifts:int} */
    private function inPeriod(int $organizationId, DashboardPeriod $p): array
    {
        $applied = WfApplication::query()->where('organization_id', $organizationId)->where('status', 'applied')->whereBetween('settled_at', [$p->fromUtc(), $p->toUtc()]);
        $gifts = WfFundEntry::query()->where('organization_id', $organizationId)->where('entry_type', 'donation')->whereBetween('occurred_at', [$p->fromUtc(), $p->toUtc()]);

        return ['given' => (float) (clone $applied)->sum('amount'), 'discounts' => (clone $applied)->count(), 'patients' => (clone $applied)->distinct()->count('patient_id'), 'received' => (float) (clone $gifts)->sum('amount'), 'gifts' => (clone $gifts)->count()];
    }
}
