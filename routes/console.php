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

Schedule::command('appointments:send-due-reminders')
    ->everyFiveMinutes()
    ->name('send-due-appointment-reminders')
    ->withoutOverlapping();

Schedule::command('ipd:expire-bed-reservations')
    ->everyFifteenMinutes()
    ->name('ipd-expire-bed-reservations')
    ->withoutOverlapping();

Schedule::command('ipd:generate-bed-occupancy-charges')
    ->dailyAt('00:30')
    ->name('ipd-generate-bed-occupancy-charges')
    ->withoutOverlapping();

Schedule::command('ipd:detect-delayed-discharges')
    ->dailyAt('08:00')
    ->name('ipd-detect-delayed-discharges')
    ->withoutOverlapping();

Schedule::command('ipd:reconcile-bed-states')
    ->hourly()
    ->name('ipd-reconcile-bed-states')
    ->withoutOverlapping();

Schedule::command('nursing:detect-overdue-tasks')->everyFifteenMinutes()->name('nursing-detect-overdue-tasks')->withoutOverlapping();
Schedule::command('nursing:detect-overdue-medication-administration')->everyFiveMinutes()->name('nursing-detect-overdue-medication')->withoutOverlapping();
Schedule::command('nursing:generate-medication-schedule')->everyThirtyMinutes()->name('nursing-generate-medication-schedule')->withoutOverlapping();
