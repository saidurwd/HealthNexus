<?php

namespace App\Services\Analytics\Loaders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** The calendar, from the earliest date in the data (or two years back) to a year ahead. Rebuilt each run; it is small. */
class DateDimension extends Loader
{
    public function key(): string
    {
        return 'dim_date';
    }

    public function label(): string
    {
        return 'Calendar';
    }

    public function kind(): string
    {
        return 'dimension';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'dim_date';
    }

    protected function uniqueBy(): array
    {
        return ['date_key'];
    }

    public function load(?string $watermark): array
    {
        $earliest = collect([DB::table('encounters')->min('encounter_date'), DB::table('billing_invoices')->min('invoice_date'), DB::table('appointments')->min('appointment_date')])->filter()->map(fn ($d) => substr((string) $d, 0, 10))->min();
        $from = CarbonImmutable::parse($earliest ?? now()->subYears(2))->min(now()->subYears(2))->startOfMonth();
        $to = now()->addYears((int) config('analytics.load.calendar_years_ahead', 1))->endOfYear()->toImmutable();
        $rows = [];
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $rows[] = ['date_key' => (int) $d->format('Ymd'), 'date' => $d->toDateString(), 'year' => $d->year, 'quarter' => $d->quarter, 'month' => $d->month, 'month_name' => $d->format('F'), 'year_month' => $d->format('Y-m'),
                'week' => $d->isoWeek, 'day_of_week' => $d->isoWeekday(), 'day_name' => $d->format('l'), 'is_weekend' => in_array($d->isoWeekday(), [5, 6], true)]; // Friday and Saturday are the weekend in Bangladesh
        }
        $written = $this->warehouse->upsert('dim_date', $rows, ['date_key']);

        return ['read' => count($rows), 'written' => $written, 'watermark' => $to->toDateString()];
    }
}
