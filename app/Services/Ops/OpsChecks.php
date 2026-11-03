<?php

namespace App\Services\Ops;

use App\Models\BackupRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Machine-checkable operations baseline (guideline 21.5) used by `ops:check` (every few minutes, exit code for the alert tool) and by
 * `pilot:readiness` (go-live checklist F-4). Each check returns status ok | warn | fail and a plain sentence.
 */
class OpsChecks
{
    public const HEARTBEAT_KEY = 'ops.scheduler_heartbeat';

    /** @return list<array{name: string, status: string, detail: string}> */
    public function monitoring(): array
    {
        $c = config('ops.monitoring');
        $checks = [];

        $checks[] = $this->safe('Database', function () {
            DB::select('select 1');

            return ['ok', 'Connected.'];
        });

        $checks[] = $this->safe('Failed jobs', function () use ($c) {
            $n = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

            return [$n > $c['failed_jobs_max'] ? 'fail' : 'ok', "$n failed job(s)."];
        });

        $checks[] = $this->safe('Queue depth', function () use ($c) {
            $n = config('queue.default') === 'database' && Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;

            return [$n > $c['queue_depth_max'] ? 'fail' : 'ok', "$n waiting job(s)."];
        });

        $checks[] = $this->safe('Disk space', function () use ($c) {
            $free = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $percent = $total > 0 ? round(100 * $free / $total, 1) : 0;

            return [$percent < $c['disk_free_min_percent'] ? 'fail' : 'ok', "$percent% free."];
        });

        $checks[] = $this->safe('Scheduler heartbeat', function () use ($c) {
            $last = Cache::get(self::HEARTBEAT_KEY);
            if ($last === null) {
                return ['fail', 'The scheduler has never reported. Is `schedule:run` in cron?'];
            }
            $minutes = (int) floor((time() - $last) / 60);

            return [$minutes > $c['scheduler_heartbeat_max_minutes'] ? 'fail' : 'ok', "Last beat $minutes minute(s) ago."];
        });

        $checks[] = $this->safe('Critical alerts waiting', function () use ($c) {
            if (! Schema::hasTable('clinical_alerts')) {
                return ['ok', 'No alert table.'];
            }
            $n = DB::table('clinical_alerts')->where('status', 'open')->where('severity', 'critical')->where('ack_required', true)
                ->where('raised_at', '<', now()->subMinutes($c['unacknowledged_critical_alert_minutes']))->count();

            return [$n > 0 ? 'fail' : 'ok', "$n critical alert(s) unacknowledged for more than {$c['unacknowledged_critical_alert_minutes']} minutes."];
        });

        return array_merge($checks, $this->backups());
    }

    /** @return list<array{name: string, status: string, detail: string}> */
    public function backups(): array
    {
        return [
            $this->safe('Last backup', function () {
                $run = BackupRun::query()->where('type', 'backup')->where('status', 'succeeded')->latest('id')->first();
                if (! $run) {
                    return ['fail', 'No successful backup has ever been recorded.'];
                }
                $hours = (int) floor($run->finished_at->diffInHours(now(), true));

                return [$hours > config('ops.backup.max_age_hours') ? 'fail' : 'ok', "Last good backup $hours hour(s) ago (".($run->encrypted ? 'encrypted' : 'NOT encrypted').').'];
            }),
            $this->safe('Last restore drill', function () {
                $run = BackupRun::query()->where('type', 'restore_drill')->where('status', 'succeeded')->latest('id')->first();
                if (! $run) {
                    return ['fail', 'No successful restore drill has been recorded. A backup that was never restored is not a backup.'];
                }
                $days = (int) floor($run->finished_at->diffInDays(now(), true));
                $signed = $run->signed_off_at ? "signed off by {$run->signed_off_by}" : 'NOT signed off';

                return [$days > config('ops.restore_drill.max_age_days') || ! $run->signed_off_at ? 'fail' : 'ok', "Last passed drill $days day(s) ago, $signed."];
            }),
        ];
    }

    /** @return array{name: string, status: string, detail: string} */
    private function safe(string $name, callable $check): array
    {
        try {
            [$status, $detail] = $check();
        } catch (\Throwable $e) {
            [$status, $detail] = ['fail', 'Check could not run: '.mb_substr($e->getMessage(), 0, 160)];
        }

        return ['name' => $name, 'status' => $status, 'detail' => $detail];
    }
}
