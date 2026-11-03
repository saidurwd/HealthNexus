<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** The middle and the slow end of a set of waiting times: the median (half were quicker) and the 90th percentile (nine in ten were quicker), nearest-rank, in whole minutes. Null when there is nothing to measure. */
class Spread
{
    /**
     * @param  Collection<int, int>  $minutes
     * @return array{median:int,p90:int,n:int}|null
     */
    public static function of(Collection $minutes): ?array
    {
        $sorted = $minutes->sort()->values();
        if ($sorted->isEmpty()) {
            return null;
        }
        $at = fn (int $p) => $sorted[max(0, (int) ceil($p / 100 * $sorted->count()) - 1)];

        return ['median' => $at(50), 'p90' => $at(90), 'n' => $sorted->count()];
    }

    public static function minutesBetween(mixed $from, mixed $to): int
    {
        return max(0, (int) round((CarbonImmutable::parse((string) $to, 'UTC')->getTimestamp() - CarbonImmutable::parse((string) $from, 'UTC')->getTimestamp()) / 60));
    }
}
