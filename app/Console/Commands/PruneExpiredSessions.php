<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredSessions extends Command
{
    protected $signature = 'sessions:prune-expired';

    protected $description = 'Delete database session rows older than the configured session lifetime';

    public function handle(): int
    {
        if (config('session.driver') !== 'database') {
            $this->info('Session driver is not "database" — nothing to prune.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('last_activity', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} expired session(s).");

        return self::SUCCESS;
    }
}
