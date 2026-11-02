<?php

namespace App\Services\Ai\UseCases;

use App\Models\Ai\AiOutput;
use App\Models\User;
use App\Services\Ai\AiCall;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Use case 3: how much of an item will be used. The arithmetic is done here, plainly: the weekly pattern of issues and dispensing is assumed to continue; the range is the spread seen for each weekday; the error is how far
 * the same method was off on the last days it had not seen. The result is advisory. Nothing is ordered from it.
 */
class InventoryForecast
{
    public function __construct(private readonly AiGateway $gateway, private readonly AiPolicy $policy) {}

    public function forecast(User $user, int $itemId, ?int $organizationId = null): AiOutput
    {
        $org = $organizationId ?? (int) $user->organizations()->value('organizations.id');
        $name = DB::table('inventory_items')->where('organization_id', $org)->where('id', $itemId)->value('name');
        $history = $this->policy->int('forecast.history_days', 90);
        $horizon = $this->policy->int('forecast.horizon_days', 14);
        $today = CarbonImmutable::now((string) config('ai.timezone', 'Asia/Dhaka'))->startOfDay();
        $series = $this->series($org, $itemId, $today->subDays($history), $today->subDay());
        $items = [];
        if ($name === null || array_sum($series) <= 0 || count(array_filter($series)) < 7) {
            throw ValidationException::withMessages(['item' => __('There is not enough use of this item in the records to forecast it.')]);
        }
        [$total, $low, $high] = $this->project($series, $horizon, $today->subDays($history));
        $train = array_slice($series, 0, $history - $horizon, true);
        $test = array_slice($series, $history - $horizon, null, true);
        [$testTotal] = $this->project($train, $horizon, $today->subDays($history));
        $actual = array_sum($test);
        $error = $actual > 0 ? round(100 * abs($testTotal - $actual) / $actual) : null;
        $items[] = ['ref' => 'fc:total', 'text' => __('Expected use of :i over the next :d days: about :t units (likely between :l and :h).', ['i' => $name, 'd' => $horizon, 't' => $total, 'l' => $low, 'h' => $high]), 'section' => __('Forecast'), 'kind' => 'forecast'];
        $items[] = ['ref' => 'fc:error', 'text' => $error === null ? __('The method could not be tested on recent days.') : __('On the last :d days it had not seen, the same method was off by :e% in total.', ['d' => $horizon, 'e' => $error]), 'section' => __('How reliable'), 'kind' => 'forecast'];
        $items[] = ['ref' => 'fc:basis', 'text' => __('Based on :n days of issues and dispensing; the usual weekly pattern is assumed to continue. Holidays, outbreaks and supply changes are not known to it.', ['n' => $history]), 'section' => __('How reliable'), 'kind' => 'forecast'];

        return $this->gateway->run(new AiCall($org, $user, 'inventory_forecast', 'forecast', $items, 'forecast', __('Forecast of use: :i', ['i' => $name]), null, 'en', $error === null ? __('Reliability unknown.') : __('Past error about :e%.', ['e' => $error])));
    }

    /** Units used per day (positive). @return array<int,float> index 0 = the oldest day */
    private function series(int $org, int $item, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = DB::table('stock_ledger')->where('organization_id', $org)->where('item_id', $item)->whereIn('txn_type', ['dispense', 'issue'])->where('quantity', '<', 0)->whereBetween('performed_at', [$from->utc(), $to->endOfDay()->utc()])
            ->get(['quantity', 'performed_at']);
        $days = (int) $from->diffInDays($to) + 1;
        $out = array_fill(0, $days, 0.0);
        foreach ($rows as $r) {
            $i = (int) $from->diffInDays(CarbonImmutable::parse($r->performed_at)->setTimezone((string) config('ai.timezone', 'Asia/Dhaka'))->startOfDay());
            if (isset($out[$i])) {
                $out[$i] += abs((float) $r->quantity);
            }
        }

        return $out;
    }

    /** @param array<int,float> $series @return array{0:int,1:int,2:int} total, low, high over the horizon */
    private function project(array $series, int $horizon, CarbonImmutable $start): array
    {
        $byDow = [];
        foreach ($series as $i => $v) {
            $byDow[$start->addDays($i)->dayOfWeek][] = $v;
        }
        $total = $var = 0.0;
        $next = $start->addDays(count($series));
        for ($k = 0; $k < $horizon; $k++) {
            $vals = $byDow[$next->addDays($k)->dayOfWeek] ?? [0.0];
            $mean = array_sum($vals) / max(1, count($vals));
            $total += $mean;
            $var += collect($vals)->sum(fn ($x) => ($x - $mean) ** 2) / max(1, count($vals));
        }
        $sd = sqrt($var);

        return [(int) round($total), (int) max(0, round($total - $sd)), (int) round($total + $sd)];
    }
}
