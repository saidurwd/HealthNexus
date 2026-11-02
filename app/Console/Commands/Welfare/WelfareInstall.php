<?php

namespace App\Console\Commands\Welfare;

use App\Models\Organization;
use App\Services\Welfare\WelfareInstaller;
use Illuminate\Console\Command;

class WelfareInstall extends Command
{
    protected $signature = 'welfare:install {--organization= : Only this hospital}';

    protected $description = 'Add the example welfare funds (zakat, general donations, hospital charity). Safe to run again.';

    public function handle(WelfareInstaller $installer): int
    {
        $ids = $this->option('organization') ? [(int) $this->option('organization')] : Organization::query()->where('is_active', true)->pluck('id')->all();
        foreach ($ids as $id) {
            $this->line("Hospital $id: ".json_encode($installer->install((int) $id)));
        }

        return self::SUCCESS;
    }
}
