<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Watch\WatchInstaller;
use Illuminate\Console\Command;

class WatchInstall extends Command
{
    protected $signature = 'watch:install';

    protected $description = 'Add the starting list of conditions to watch for every hospital (safe to run again).';

    public function handle(WatchInstaller $installer): int
    {
        $n = 0;
        foreach (Organization::query()->orderBy('id')->get() as $org) {
            $n += $installer->install((int) $org->id)['conditions'];
        }
        $this->info("{$n} condition(s) added.");

        return self::SUCCESS;
    }
}
