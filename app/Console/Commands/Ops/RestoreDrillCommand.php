<?php

namespace App\Console\Commands\Ops;

use App\Services\Ops\BackupService;
use Illuminate\Console\Command;

class RestoreDrillCommand extends Command
{
    protected $signature = 'backup:restore-drill {--sign= : Name of the person signing the drill off in the runbook}';

    protected $description = 'Restore the latest backup into a scratch database, verify it and drop it';

    public function handle(BackupService $backups): int
    {
        try {
            $drill = $backups->restoreDrill();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($drill->status !== 'succeeded') {
            $this->error('Restore drill FAILED: '.$drill->error);

            return self::FAILURE;
        }

        $this->info("Restore drill passed: {$drill->details['tables_restored']} tables restored and checked.");
        foreach ($drill->details['counts'] as $table => $c) {
            $this->line(sprintf('  %-18s backup %d  restored %d', $table, $c['backup'], $c['restored']));
        }

        if ($name = $this->option('sign')) {
            $backups->signOff($drill, $name);
            $this->info("Signed off by $name.");
        } else {
            $this->warn('A person must still sign this drill off: php artisan backup:restore-drill --sign="Name" (or record it in the runbook).');
        }

        return self::SUCCESS;
    }
}
