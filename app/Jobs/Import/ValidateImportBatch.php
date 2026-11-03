<?php

namespace App\Jobs\Import;

use App\Models\ImportBatch;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ValidateImportBatch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public readonly int $batchId) {}

    public function handle(ImportService $imports): void
    {
        $batch = ImportBatch::query()->find($this->batchId);

        // The batch row carries its organization; a batch that no longer exists or is not freshly uploaded is ignored.
        if ($batch && in_array($batch->status, ['uploaded', 'validated', 'failed'], true)) {
            $imports->validate($batch);
        }
    }
}
