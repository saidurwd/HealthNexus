<?php

namespace App\Console\Commands\Ops;

use App\Services\Ops\OpsChecks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HeartbeatCommand extends Command
{
    protected $signature = 'ops:heartbeat';

    protected $description = 'Record that the scheduler is running (checked by ops:check)';

    public function handle(): int
    {
        Cache::forever(OpsChecks::HEARTBEAT_KEY, time());

        return self::SUCCESS;
    }
}
