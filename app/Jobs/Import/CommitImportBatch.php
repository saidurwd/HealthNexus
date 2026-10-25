<?php

namespace App\Jobs\Import;

use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Import\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CommitImportBatch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly int $batchId, public readonly int $userId) {}

    public function handle(ImportService $imports): void
    {
        $batch = ImportBatch::query()->find($this->batchId);
        $user = User::query()->find($this->userId);

        if ($batch && $user && $batch->status === 'validated') {
            $imports->commit($batch, $user);
        }
    }
}
