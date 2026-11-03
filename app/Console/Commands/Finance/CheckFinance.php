<?php

namespace App\Console\Commands\Finance;

use App\Models\Finance\Journal;
use App\Models\Finance\Period;
use App\Models\Finance\Posting;
use App\Models\Organization;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use App\Services\Finance\PostingEngine;
use Illuminate\Console\Command;

/** Daily watch over the books: postings that failed, journals waiting too long, a period that should have been locked. */
class CheckFinance extends Command
{
    protected $signature = 'finance:check';

    protected $description = 'Report failed postings, journals waiting too long and open old periods';

    public function handle(PostingEngine $engine, AlertService $alerts): int
    {
        foreach (Organization::query()->where('is_active', true)->get() as $org) {
            if (! $engine->enabled($org->id)) {
                continue;
            }
            $failed = Posting::query()->where(['organization_id' => $org->id, 'status' => 'failed'])->count();
            $waiting = Journal::query()->where(['organization_id' => $org->id, 'status' => 'pending'])->where('submitted_at', '<', now()->subDays(3))->count();
            $oldOpen = Period::query()->where(['organization_id' => $org->id, 'status' => 'open'])->whereDate('ends_on', '<', now()->subDays(45)->toDateString())->count();
            foreach ([['failed', $failed, "$failed money entr".($failed === 1 ? 'y' : 'ies').' could not be posted to the books.'], ['waiting', $waiting, "$waiting journal(s) have waited over 3 days for approval."], ['period', $oldOpen, "$oldOpen finished period(s) are still open; lock them."]] as [$key, $n, $text]) {
                if ($n > 0) {
                    try {
                        $alerts->raise(new AlertRequest(organizationId: $org->id, category: 'finance', severity: 'warning', message: $text, sourceModule: 'finance', sourceType: 'finance_check', sourceId: 0, dedupKey: 'finance-'.$key.'-'.$org->id, ackRequired: false));
                    } catch (\Throwable) {
                        // a report must never stop the check
                    }
                }
            }
            $this->info("{$org->code}: $failed failed posting(s), $waiting journal(s) waiting over 3 days, $oldOpen old period(s) still open.");
        }

        return self::SUCCESS;
    }
}
