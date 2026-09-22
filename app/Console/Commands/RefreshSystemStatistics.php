<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Snapshots cheap-to-query system-wide counters into cache so dashboard widgets can read them
 * without hitting the database on every page load. Scheduled to run periodically; the dashboard
 * falls back to a live query if the cache is empty (e.g. right after a cache flush).
 */
class RefreshSystemStatistics extends Command
{
    public const CACHE_KEY = 'hms_system_statistics';

    protected $signature = 'system:refresh-statistics';

    protected $description = 'Refresh the cached system-wide statistics snapshot used by dashboard widgets';

    public function handle(): int
    {
        $stats = [
            'total_users' => User::count(),
            'total_active_users' => User::where('is_active', true)->count(),
            'total_companies' => Company::count(),
            'total_branches' => Branch::count(),
            'total_patients' => class_exists(Patient::class) ? Patient::count() : 0,
            'generated_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY, $stats, now()->addHours(6));

        $this->info('System statistics snapshot refreshed.');

        return self::SUCCESS;
    }
}
