<?php

namespace App\Console\Commands\Quality;

use App\Models\Organization;
use App\Services\Quality\IndicatorService;
use Illuminate\Console\Command;

class QualityIndicators extends Command
{
    protected $signature = 'quality:indicators {--months=12 : How many months back to work out}';

    protected $description = 'Work out the quality indicators for the last months.';

    public function handle(IndicatorService $indicators): int
    {
        $indicators->install();
        foreach (Organization::query()->where('is_active', true)->pluck('id') as $id) {
            $this->line("Hospital $id: ".$indicators->computeRecent((int) $id, (int) $this->option('months')).' value(s).');
        }

        return self::SUCCESS;
    }
}
