<?php

namespace App\Console\Commands\Quality;

use App\Models\Organization;
use App\Services\Quality\QualityInstaller;
use Illuminate\Console\Command;

class QualityInstall extends Command
{
    protected $signature = 'quality:install {--organization= : Only this hospital}';

    protected $description = 'Add the starter quality content (indicators, committees, privilege list, example checklist). Safe to run again.';

    public function handle(QualityInstaller $installer): int
    {
        $ids = $this->option('organization') ? [(int) $this->option('organization')] : Organization::query()->where('is_active', true)->pluck('id')->all();
        $installer->install();
        foreach ($ids as $id) {
            $r = $installer->install((int) $id);
            $this->line("Hospital $id: {$r['committees']} committee(s), {$r['privileges']} privilege(s), {$r['standards']} checklist(s) added.");
        }

        return self::SUCCESS;
    }
}
