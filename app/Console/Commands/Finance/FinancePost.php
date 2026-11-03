<?php

namespace App\Console\Commands\Finance;

use App\Models\Organization;
use App\Services\Finance\PostingEngine;
use App\Services\Finance\StockPostingService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

/** Posts what other modules did and the books have not seen yet: missed Billing events, goods received, the day's stock movements, then the failed postings again. */
class FinancePost extends Command
{
    protected $signature = 'finance:post {--organization= : organization id (default: every hospital with Finance on)} {--days= : how far back to look for missed Billing records}';

    protected $description = 'Post missed Billing, insurance and stock entries to the books and retry failed ones';

    public function handle(PostingEngine $engine, StockPostingService $stock, ModuleManager $modules): int
    {
        $orgs = Organization::query()->where('is_active', true)->when($this->option('organization'), fn ($q, $id) => $q->where('id', $id))->get();
        foreach ($orgs as $org) {
            if (! $engine->enabled($org->id)) {
                continue;
            }
            $caught = array_sum($engine->catchUp($org->id, $this->option('days') ? (int) $this->option('days') : null));
            $receipts = $stock->postReceipts($org->id);
            $moves = $stock->postMovements($org->id);
            $retry = $engine->retryFailed($org->id);
            $this->info("{$org->code}: $caught missed, $receipts goods receipts, {$moves['journals']} stock journals ({$moves['unvalued']} movements without a cost), {$retry['posted']} of {$retry['retried']} retried.");
        }

        return self::SUCCESS;
    }
}
