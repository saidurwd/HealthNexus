<?php

namespace App\Console\Commands\Analytics;

use App\Models\Organization;
use App\Services\Analytics\Kpi\DashboardSnapshotService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;

class AnalyticsDashboardPack extends Command
{
    protected $signature = 'analytics:dashboard-pack {--month= : The month to keep, as YYYY-MM (default: last month)}';

    protected $description = 'Keep every system dashboard as it looked for a month (the month-end pack).';

    public function handle(DashboardSnapshotService $snapshots): int
    {
        $tz = (string) config('analytics.timezone', 'Asia/Dhaka');
        try {
            $month = $this->option('month') ? CarbonImmutable::createFromFormat('!Y-m', (string) $this->option('month'), $tz) : CarbonImmutable::now($tz)->subMonthNoOverflow()->startOfMonth();
        } catch (InvalidFormatException) {
            $month = false;
        }
        if ($month === false) {
            $this->error('Use YYYY-MM for the month.');

            return self::INVALID;
        }
        $total = 0;
        foreach (Organization::query()->orderBy('id')->get() as $org) {
            $total += $snapshots->monthlyPack((int) $org->id, $month);
        }
        $this->info("Kept {$total} dashboard snapshot(s) for ".$month->format('F Y').'.');

        return self::SUCCESS;
    }
}
