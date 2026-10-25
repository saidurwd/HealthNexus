<?php

namespace App\Console\Commands\Ops;

use App\Models\Organization;
use App\Services\Ops\DowntimeCensus;
use Illuminate\Console\Command;

class DowntimeCensusCommand extends Command
{
    protected $signature = 'downtime:census';

    protected $description = 'Store a printable, read-only list of current inpatients for use during downtime';

    public function handle(DowntimeCensus $census): int
    {
        Organization::query()->where('is_active', true)->each(function (Organization $organization) use ($census) {
            $result = $census->generate($organization);
            $this->line($organization->code.': '.($result ? "{$result['patients']} inpatient(s) → {$result['path']}" : 'no inpatients'));
        });

        return self::SUCCESS;
    }
}
