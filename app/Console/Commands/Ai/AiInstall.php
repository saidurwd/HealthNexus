<?php

namespace App\Console\Commands\Ai;

use App\Models\Organization;
use App\Services\Ai\AiInstaller;
use Illuminate\Console\Command;

class AiInstall extends Command
{
    protected $signature = 'ai:install {--organization= : Only this hospital}';

    protected $description = 'Put the register of AI functions and the starter evaluation set in place (nothing is switched on, no outside service is contacted). Safe to run again.';

    public function handle(AiInstaller $installer): int
    {
        $ids = $this->option('organization') ? [(int) $this->option('organization')] : Organization::query()->where('is_active', true)->pluck('id')->all();
        foreach ($ids as $id) {
            $this->line("Hospital $id: ".json_encode($installer->install((int) $id)));
        }

        return self::SUCCESS;
    }
}
