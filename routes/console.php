<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Heartbeat: System Health reads this timestamp to tell whether the scheduler is actually
// running (as opposed to just being configured) — a stale value means the cron entry that
// should be calling `php artisan schedule:run` every minute isn't firing.
Schedule::call(fn () => Cache::put('hms_scheduler_heartbeat', now()->toIso8601String(), now()->addHours(2)))
    ->everyMinute()
    ->name('scheduler-heartbeat')
    ->withoutOverlapping();

Schedule::command('sessions:prune-expired')
    ->hourly()
    ->name('prune-expired-sessions')
    ->withoutOverlapping();

Schedule::command('audit:prune')
    ->dailyAt('02:00')
    ->name('prune-audit-logs')
    ->withoutOverlapping();

Schedule::command('system:refresh-statistics')
    ->hourly()
    ->name('refresh-system-statistics')
    ->withoutOverlapping();

Schedule::command('queue:prune-failed', ['--hours' => 24 * 30])
    ->daily()
    ->name('prune-failed-jobs')
    ->withoutOverlapping();
