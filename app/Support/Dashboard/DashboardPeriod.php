<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The period a dashboard shows, read from the request (`period=7d`, or `period=custom&from=…&to=…`). Days start and end in Dhaka time; queries on UTC columns use fromUtc()/toUtc(). A bad or missing value falls back to the
 * default; a custom range is limited in length. `previous()` is the stretch of the same length straight before, for "against the period before".
 */
final class DashboardPeriod
{
    public function __construct(public readonly string $key, public readonly CarbonImmutable $from, public readonly CarbonImmutable $to) {}

    /** @param  list<string>|null  $allowed */
    public static function fromRequest(Request $request, ?string $default = null, ?array $allowed = null): self
    {
        return self::make((string) $request->query('period', ''), $request->query('from'), $request->query('to'), $default, $allowed);
    }

    /** @param  list<string>|null  $allowed */
    public static function make(string $key, mixed $from = null, mixed $to = null, ?string $default = null, ?array $allowed = null, ?CarbonImmutable $now = null): self
    {
        $tz = (string) config('ux.timezone', 'Asia/Dhaka');
        $now = ($now ?? CarbonImmutable::now())->setTimezone($tz);
        $allowed ??= (array) config('ux.dashboard.periods');
        $default ??= (string) config('ux.dashboard.default_period', '30d');
        $key = in_array($key, $allowed, true) ? $key : $default;
        $today = $now->startOfDay();

        $period = match ($key) {
            'today' => new self('today', $today, $today->endOfDay()),
            '7d' => new self('7d', $today->subDays(6), $today->endOfDay()),
            '90d' => new self('90d', $today->subDays(89), $today->endOfDay()),
            'month' => new self('month', $today->startOfMonth(), $today->endOfDay()),
            'last_month' => new self('last_month', $today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            'custom' => self::custom($from, $to, $tz, $today) ?? self::make($default === 'custom' ? '30d' : $default, null, null, $default, $allowed, $now),
            default => new self('30d', $today->subDays(29), $today->endOfDay()),
        };

        return $period;
    }

    private static function custom(mixed $from, mixed $to, string $tz, CarbonImmutable $today): ?self
    {
        if (trim((string) $from) === '' || trim((string) $to) === '') {
            return null;
        }
        try {
            $f = CarbonImmutable::parse((string) $from, $tz)->startOfDay();
            $t = CarbonImmutable::parse((string) $to, $tz)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
        if ($f->gt($t) || $t->gt($today->endOfDay()) || $f->diffInDays($t) > (int) config('ux.dashboard.max_custom_days', 366)) {
            return null;
        }

        return new self('custom', $f, $t);
    }

    public function days(): int
    {
        return (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    public function fromUtc(): CarbonImmutable
    {
        return $this->from->utc();
    }

    public function toUtc(): CarbonImmutable
    {
        return $this->to->utc();
    }

    /** The stretch of the same length straight before this one (the whole previous month for the month periods). */
    public function previous(): self
    {
        if ($this->key === 'month') {
            $start = $this->from->subMonthNoOverflow()->startOfMonth();

            return new self('custom', $start, $start->endOfMonth());
        }
        if ($this->key === 'last_month') {
            $start = $this->from->subMonthNoOverflow()->startOfMonth();

            return new self('custom', $start, $start->endOfMonth());
        }
        $days = $this->days();

        return new self('custom', $this->from->subDays($days), $this->from->subDay()->endOfDay());
    }

    public function label(): string
    {
        return match ($this->key) {
            'today' => __('Today'),
            '7d' => __('Last 7 days'),
            '30d' => __('Last 30 days'),
            '90d' => __('Last 90 days'),
            'month' => __('This month'),
            'last_month' => __('Last month'),
            default => $this->from->format('d M Y').' – '.$this->to->format('d M Y'),
        };
    }

    /** @return array<string,string> the query string that gives this period back */
    public function query(): array
    {
        return $this->key === 'custom' ? ['period' => 'custom', 'from' => $this->fromDate(), 'to' => $this->toDate()] : ['period' => $this->key];
    }

    /** @return list<string> keys of the periods a filter bar offers */
    public static function options(?array $allowed = null): array
    {
        return array_values($allowed ?? (array) config('ux.dashboard.periods'));
    }
}
