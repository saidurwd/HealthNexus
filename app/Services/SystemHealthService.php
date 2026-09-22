<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read-only health probes for the pieces of infrastructure this HMS depends on. Every check
 * catches its own exceptions — a down dependency (e.g. Redis not running) must degrade its own
 * row, never crash the health page itself.
 */
class SystemHealthService
{
    public const HEALTHY = 'healthy';

    public const DEGRADED = 'degraded';

    public const DOWN = 'down';

    /**
     * @return array<string, array{status:string,message:string,latency_ms:?float}>
     */
    public function check(): array
    {
        return [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'mail' => $this->checkMail(),
            'scheduler' => $this->checkScheduler(),
        ];
    }

    private function checkApplication(): array
    {
        $debugInProduction = app()->environment('production') && config('app.debug');

        return [
            'status' => $debugInProduction ? self::DEGRADED : self::HEALTHY,
            'message' => $debugInProduction
                ? 'APP_DEBUG is enabled in production — this exposes stack traces to users.'
                : sprintf('Laravel %s, PHP %s, environment: %s', app()->version(), PHP_VERSION, app()->environment()),
            'latency_ms' => null,
        ];
    }

    private function checkDatabase(): array
    {
        return $this->timed(function () {
            DB::select('select 1');

            return sprintf('Connected to %s.', config('database.connections.'.config('database.default').'.database'));
        });
    }

    private function checkRedis(): array
    {
        return $this->timed(function () {
            $pong = \Illuminate\Support\Facades\Redis::connection()->ping();

            return 'Redis responded: '.(is_string($pong) ? $pong : 'PONG');
        });
    }

    private function checkQueue(): array
    {
        return $this->timed(function () {
            $connection = config('queue.default');
            $failedCount = DB::table('failed_jobs')->count();

            return sprintf('Connection: %s. Failed jobs: %d.', $connection, $failedCount);
        });
    }

    private function checkStorage(): array
    {
        return $this->timed(function () {
            $disk = config('filesystems.default');
            $path = 'health-check/'.Str::random(16).'.txt';

            Storage::disk($disk)->put($path, 'ok');
            $readBack = Storage::disk($disk)->get($path);
            Storage::disk($disk)->delete($path);

            if ($readBack !== 'ok') {
                throw new \RuntimeException('Storage read-back did not match what was written.');
            }

            return sprintf('Disk "%s" is writable and readable.', $disk);
        });
    }

    private function checkMail(): array
    {
        return $this->timed(function () {
            $mailer = config('mail.default');
            $config = config("mail.mailers.{$mailer}");

            if (! $config) {
                throw new \RuntimeException("Mailer \"{$mailer}\" has no configuration.");
            }

            if (in_array($mailer, ['smtp', 'ses', 'postmark', 'resend'], true) && empty($config['host'] ?? $config['key'] ?? null)) {
                throw new \RuntimeException("Mailer \"{$mailer}\" is missing required configuration.");
            }

            return sprintf('Mailer configured: %s.', $mailer);
        });
    }

    private function checkScheduler(): array
    {
        $heartbeat = Cache::get('hms_scheduler_heartbeat');

        if (! $heartbeat) {
            return [
                'status' => self::DEGRADED,
                'message' => 'No scheduler heartbeat recorded yet. Confirm the server cron entry runs "php artisan schedule:run" every minute.',
                'latency_ms' => null,
            ];
        }

        $age = now()->diffInMinutes($heartbeat);

        if ($age > 5) {
            return [
                'status' => self::DOWN,
                'message' => "Last scheduler heartbeat was {$age} minute(s) ago — the cron entry may not be running.",
                'latency_ms' => null,
            ];
        }

        return [
            'status' => self::HEALTHY,
            'message' => "Last heartbeat {$age} minute(s) ago.",
            'latency_ms' => null,
        ];
    }

    private function timed(callable $probe): array
    {
        $start = microtime(true);

        try {
            $message = $probe();

            return [
                'status' => self::HEALTHY,
                'message' => $message,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::DOWN,
                'message' => $e->getMessage(),
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        }
    }
}
