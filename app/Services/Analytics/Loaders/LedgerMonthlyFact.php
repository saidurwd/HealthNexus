<?php

namespace App\Services\Analytics\Loaders;

use App\Services\Finance\LedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Finance balances by month, taken from the books (Finance's own trial balance). Analytics never recomputes accounting from Billing. */
class LedgerMonthlyFact extends Loader
{
    public function key(): string
    {
        return 'fact_ledger_monthly';
    }

    public function label(): string
    {
        return 'Finance balances by month';
    }

    public function mode(): string
    {
        return 'snapshot';
    }

    protected function target(): string
    {
        return 'fact_ledger_monthly';
    }

    protected function uniqueBy(): array
    {
        return ['organization_id', 'period_ym', 'account_code'];
    }

    public function load(?string $watermark): array
    {
        if (! Schema::hasTable('fin_periods')) {
            return ['read' => 0, 'written' => 0, 'watermark' => null];
        }
        $ledger = app(LedgerService::class);
        $read = $written = 0;
        $now = now()->toDateTimeString();
        foreach (DB::table('fin_periods')->where('starts_on', '<=', now()->toDateString())->orderBy('starts_on')->get(['organization_id', 'starts_on', 'ends_on']) as $p) {
            $from = CarbonImmutable::parse($p->starts_on)->toDateString();
            $to = CarbonImmutable::parse($p->ends_on)->toDateString();
            $rows = [];
            foreach ($ledger->trialBalance((int) $p->organization_id, $from, $to, null, false, true) as $r) {
                $rows[] = ['organization_id' => $p->organization_id, 'period_ym' => substr($from, 0, 7), 'account_code' => $r->code, 'account_name' => $r->name, 'account_type' => $r->type, 'debit' => $r->debit, 'credit' => $r->credit, 'closing' => $r->closing, 'loaded_at' => $now];
            }
            $read += count($rows);
            $written += $this->warehouse->upsert('fact_ledger_monthly', $rows, $this->uniqueBy());
        }

        return ['read' => $read, 'written' => $written, 'watermark' => now()->toDateString()];
    }
}
