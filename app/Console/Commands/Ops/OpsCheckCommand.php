<?php

namespace App\Console\Commands\Ops;

use App\Services\Ops\OpsChecks;
use Illuminate\Console\Command;

class OpsCheckCommand extends Command
{
    protected $signature = 'ops:check {--json : Machine-readable output for the monitoring tool}';

    protected $description = 'Run the operations monitoring baseline; exit code 1 if anything has failed';

    public function handle(OpsChecks $checks): int
    {
        $results = $checks->monitoring();

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Check', 'Status', 'Detail'], array_map(fn ($r) => [$r['name'], strtoupper($r['status']), $r['detail']], $results));
        }

        return collect($results)->contains('status', 'fail') ? self::FAILURE : self::SUCCESS;
    }
}
