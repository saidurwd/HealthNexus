<?php

namespace App\Console\Commands\Facility;

use App\Models\Organization;
use App\Services\Facility\FacilityInstaller;
use Illuminate\Console\Command;

class FacilityInstall extends Command
{
    protected $signature = 'facility:install {--organization= : Only this hospital}';

    protected $description = 'Add the starter facility content (diet types, linen types, mortuary unit, instrument sets). Safe to run again.';

    public function handle(FacilityInstaller $installer): int
    {
        $ids = $this->option('organization') ? [(int) $this->option('organization')] : Organization::query()->where('is_active', true)->pluck('id')->all();
        foreach ($ids as $id) {
            $r = $installer->install((int) $id);
            $this->line("Hospital $id: ".json_encode($r));
        }

        return self::SUCCESS;
    }
}
