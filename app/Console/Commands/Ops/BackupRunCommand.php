<?php

namespace App\Console\Commands\Ops;

use App\Services\Ops\BackupService;
use Illuminate\Console\Command;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Take an (optionally encrypted) database backup and prune old ones';

    public function handle(BackupService $backups): int
    {
        try {
            $run = $backups->backup();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Backup %s written (%s, %s).', $run->path, number_format($run->size_bytes / 1048576, 1).' MB', $run->encrypted ? 'encrypted' : 'NOT encrypted'));

        return self::SUCCESS;
    }
}
