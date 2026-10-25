<?php

use App\Jobs\Clinical\EscalateUnacknowledgedAlerts;
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

Schedule::command('ot:detect-schedule-conflicts')->everyFifteenMinutes()->name('ot-detect-schedule-conflicts')->withoutOverlapping();
Schedule::command('ot:detect-missing-preoperative-requirements')->hourly()->name('ot-detect-missing-preop')->withoutOverlapping();
Schedule::command('ot:send-surgery-reminders')->dailyAt('08:00')->name('ot-send-surgery-reminders')->withoutOverlapping();
Schedule::command('ot:generate-daily-list')->dailyAt('17:00')->name('ot-generate-daily-list')->withoutOverlapping();

Schedule::command('icu:generate-alerts')->everyFiveMinutes()->name('icu-generate-alerts')->withoutOverlapping();
Schedule::command('icu:escalate-unacknowledged-alerts')->everyFifteenMinutes()->name('icu-escalate-unacknowledged-alerts')->withoutOverlapping();
Schedule::command('icu:detect-missing-documentation')->hourly()->name('icu-detect-missing-documentation')->withoutOverlapping();
Schedule::command('icu:generate-daily-census')->dailyAt('00:05')->name('icu-generate-daily-census')->withoutOverlapping();
Schedule::command('icu:generate-charge-events')->dailyAt('00:15')->name('icu-generate-charge-events')->withoutOverlapping();

Schedule::job(new EscalateUnacknowledgedAlerts)->everyMinute()->name('clinical-escalate-unacknowledged-alerts')->withoutOverlapping();

Schedule::command('exports:prune')->hourly()->name('exports-prune')->withoutOverlapping();

Schedule::command('ops:heartbeat')->everyMinute()->name('ops-heartbeat');
Schedule::command('backup:run')->dailyAt('01:30')->name('backup-run')->withoutOverlapping();
Schedule::command('backup:restore-drill')->monthlyOn(1, '03:30')->name('backup-restore-drill')->withoutOverlapping();

Schedule::command('downtime:census')->cron('0 */'.max(1, (int) config('ops.downtime.census_hours', 6)).' * * *')->name('downtime-census')->withoutOverlapping();

Schedule::command('ed:check-timers')->everyMinute()->name('ed-check-timers')->withoutOverlapping();
