<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Counting and adding things per day in Dhaka time. Timestamps are stored in UTC, so a day in Dhaka starts at 18:00 UTC the evening before; a plain date column needs no shift.
 */
class Days
{
    public static function timezone(): string
    {
        return (string) config('ux.timezone', 'Asia/Dhaka');
    }

    public static function today(?CarbonImmutable $now = null): CarbonImmutable
    {
        return ($now ?? CarbonImmutable::now())->setTimezone(self::timezone())->startOfDay();
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} the UTC start and end of the Dhaka day */
    public static function bounds(CarbonImmutable $day): array
    {
        $d = $day->setTimezone(self::timezone());

        return [$d->startOfDay()->utc(), $d->endOfDay()->utc()];
    }

    /** SQL for the Dhaka calendar date of a UTC timestamp column. */
    public static function dateOf(string $column): string
    {
        $hours = (int) round(CarbonImmutable::now(self::timezone())->utcOffset() / 60);

        return DB::getDriverName() === 'sqlite' ? "date($column, '+$hours hours')" : "date(date_add($column, interval $hours hour))";
    }

    /** SQL for the hour of the day (0-23) in Dhaka of a UTC timestamp column. */
    public static function hourOf(string $column): string
    {
        $hours = (int) round(CarbonImmutable::now(self::timezone())->utcOffset() / 60);

        return DB::getDriverName() === 'sqlite' ? "cast(strftime('%H', $column, '+$hours hours') as integer)" : "hour(date_add($column, interval $hours hour))";
    }

    /**
     * One value per day from $from to $to (zero where nothing happened).
     *
     * @param  string  $aggregate  for example count(*) or sum(amount)
     * @param  bool  $utc  true when $column is a UTC timestamp, false when it is a plain date
     * @return array<string,float> date => value, oldest first
     */
    public static function perDay(Builder $query, string $column, CarbonImmutable $from, CarbonImmutable $to, string $aggregate = 'count(*)', bool $utc = true): array
    {
        $from = $from->setTimezone(self::timezone())->startOfDay();
        $to = $to->setTimezone(self::timezone())->startOfDay();
        $expr = $utc ? self::dateOf($column) : "date($column)";
        $q = clone $query;
        $utc ? $q->whereBetween($column, [$from->utc(), $to->endOfDay()->utc()]) : $q->whereBetween($column, [$from->toDateString(), $to->toDateString()]);
        $found = $q->selectRaw("$expr as d, $aggregate as v")->groupBy('d')->pluck('v', 'd');
        $out = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $out[$d->toDateString()] = (float) ($found[$d->toDateString()] ?? 0);
        }

        return $out;
    }
}
