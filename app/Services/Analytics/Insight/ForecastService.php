<?php

namespace App\Services\Analytics\Insight;

use App\Models\Analytics\AnalyticsForecast;
use App\Services\Analytics\Warehouse;
use Carbon\CarbonImmutable;

/**
 * A simple forecast to plan with, never to decide with: the recent average level, shaped by the day-of-week pattern, with a range from the back-test errors. It says how wrong it was on the last
 * days it had not seen (the error %). It is advisory; no decision, order or price is made from it by the system.
 */
class ForecastService
{
    /** @var array<string,string> metric => label */
    public const METRICS = ['opd_visits' => 'Outpatient visits per day', 'admissions' => 'Admissions per day', 'net_revenue' => 'Net revenue per day', 'lab_orders' => 'Lab orders per day', 'ed_visits' => 'Emergency visits per day'];

    public function __construct(private readonly Warehouse $warehouse) {}

    /** @return array<string,float> date => value, zero-filled over the last $days days before today */
    public function series(int $organizationId, string $metric, int $days): array
    {
        $end = CarbonImmutable::now((string) config('analytics.timezone', 'Asia/Dhaka'))->startOfDay()->subDay();
        $start = $end->subDays($days - 1);
        $range = [(int) $start->format('Ymd'), (int) $end->format('Ymd')];
        $q = match ($metric) {
            'opd_visits' => $this->warehouse->table('fact_encounters')->whereRaw("lower(encounter_type) in ('opd', 'outpatient')")->selectRaw('date_key, count(*) as v'),
            'admissions' => $this->warehouse->table('fact_admissions')->selectRaw('date_key, count(*) as v'),
            'net_revenue' => $this->warehouse->table('fact_revenue_lines')->where('is_billed', true)->selectRaw('date_key, sum(net) as v'),
            'lab_orders' => $this->warehouse->table('fact_lab_orders')->selectRaw('date_key, count(*) as v'),
            'ed_visits' => $this->warehouse->table('fact_ed_visits')->selectRaw('date_key, count(*) as v'),
            default => throw new \InvalidArgumentException('Unknown metric.'),
        };
        $found = $q->where('organization_id', $organizationId)->whereBetween('date_key', $range)->groupBy('date_key')->pluck('v', 'date_key');
        $out = [];
        for ($d = $start; $d->lte($end); $d = $d->addDay()) {
            $out[$d->toDateString()] = (float) ($found[(int) $d->format('Ymd')] ?? 0);
        }

        return $out;
    }

    /**
     * @param  array<string,float>  $history
     * @return array<string,float> predicted values for $horizon days after the last history day
     */
    public function predict(array $history, int $horizon): array
    {
        $dates = array_keys($history);
        $values = array_values($history);
        $n = count($values);
        $level = $n >= 28 ? array_sum(array_slice($values, -28)) / 28 : ($n > 0 ? array_sum($values) / $n : 0.0);
        $overall = $n > 0 ? array_sum($values) / $n : 0.0;
        $byDow = [];
        foreach ($history as $date => $v) {
            $byDow[CarbonImmutable::parse($date)->isoWeekday()][] = $v;
        }
        $factor = fn (int $dow) => $overall > 0 && ! empty($byDow[$dow]) ? (array_sum($byDow[$dow]) / count($byDow[$dow])) / $overall : 1.0;
        $last = CarbonImmutable::parse(end($dates) ?: now()->toDateString());
        $out = [];
        for ($i = 1; $i <= $horizon; $i++) {
            $d = $last->addDays($i);
            $out[$d->toDateString()] = max(0.0, round($level * $factor($d->isoWeekday()), 2));
        }

        return $out;
    }

    /** @return array{error_pct:?float,spread:float} mean absolute percentage error and the typical size of a miss, from predicting the last days of the history without having seen them */
    public function backtest(array $history, int $hold): array
    {
        if (count($history) < $hold + 28) {
            return ['error_pct' => null, 'spread' => 0.0];
        }
        $train = array_slice($history, 0, count($history) - $hold, true);
        $test = array_slice($history, -$hold, null, true);
        $pred = $this->predict($train, $hold);
        $pct = [];
        $miss = [];
        foreach (array_values($test) as $i => $actual) {
            $p = array_values($pred)[$i];
            $miss[] = abs($actual - $p);
            $actual > 0 && $pct[] = abs($actual - $p) / $actual * 100;
        }

        return ['error_pct' => $pct ? round(array_sum($pct) / count($pct), 1) : null, 'spread' => round(array_sum($miss) / max(1, count($miss)) * 1.25, 2)];
    }

    public function run(int $organizationId, string $metric): AnalyticsForecast
    {
        $historyDays = (int) config('analytics.forecast.history_days', 120);
        $horizon = (int) config('analytics.forecast.horizon_days', 14);
        $hold = (int) config('analytics.forecast.backtest_days', 14);
        $history = $this->series($organizationId, $metric, $historyDays);
        $bt = $this->backtest($history, $hold);
        $pred = $this->predict($history, $horizon);
        $points = ['history' => array_slice($history, -30, null, true), 'forecast' => collect($pred)->map(fn ($v) => ['value' => $v, 'low' => max(0, round($v - $bt['spread'], 2)), 'high' => round($v + $bt['spread'], 2)])->all(), 'advisory' => true];

        return AnalyticsForecast::create(['organization_id' => $organizationId, 'metric' => $metric, 'method' => 'weekly-pattern average', 'horizon_days' => $horizon, 'error_pct' => $bt['error_pct'], 'points' => $points, 'generated_at' => now()]);
    }
}
