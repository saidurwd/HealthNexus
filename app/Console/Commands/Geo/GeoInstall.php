<?php

namespace App\Console\Commands\Geo;

use App\Services\Geo\GeoInstaller;
use Illuminate\Console\Command;

class GeoInstall extends Command
{
    protected $signature = 'geo:install {--file= : A geography JSON file (default: database/data/bd-geography.json)}';

    protected $description = 'Put the divisions, districts and upazilas in the database (safe to run again).';

    public function handle(GeoInstaller $installer): int
    {
        $r = $installer->install($this->option('file') ?: null);
        $this->info("{$r['divisions']} divisions, {$r['districts']} districts and {$r['upazilas']} upazilas ({$r['created']} new).");

        return self::SUCCESS;
    }
}
