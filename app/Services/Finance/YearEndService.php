<?php

namespace App\Services\Finance;

use App\Models\Finance\FiscalYear;
use App\Models\Finance\Period;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Closing a fiscal year: every revenue and expense balance is swept into retained earnings by one journal, then every period and the year are closed for good. */
class YearEndService
{
    public function __construct(private readonly LedgerService $ledger, private readonly ChartService $chart, private readonly JournalService $journals, private readonly AuditLogger $audit) {}

    /** Things that must be true before the year can close. @return list<string> reasons (empty = ready) */
    public function blockers(FiscalYear $year): array
    {
        $org = $year->organization_id;
        $b = [];
        if ($year->status === 'closed') {
            return ['This year is already closed.'];
        }
        $pending = DB::table('fin_journals')->where('organization_id', $org)->whereBetween('journal_date', [$year->starts_on->toDateString(), $year->ends_on->toDateString()])->whereIn('status', ['draft', 'pending'])->count();
        $pending && $b[] = "$pending journal(s) are still drafts or waiting for approval.";
        $failed = DB::table('fin_postings')->where('organization_id', $org)->where('status', 'failed')->count();
        $failed && $b[] = "$failed posting(s) from other modules failed and are not in the books yet.";
        if (Period::query()->where('fiscal_year_id', $year->id)->where('status', 'open')->where('ends_on', '>', now()->toDateString())->exists()) {
            $b[] = 'The year has not ended yet.';
        }
        $tb = $this->ledger->totals($this->ledger->trialBalance($org, null, $year->ends_on->toDateString()));
        $tb['balanced'] || $b[] = 'The trial balance does not balance.';

        return $b;
    }

    public function close(FiscalYear $year, User $by): FiscalYear
    {
        if (($why = $this->blockers($year)) !== []) {
            throw ValidationException::withMessages(['year' => $why]);
        }
        $org = $year->organization_id;

        return DB::transaction(function () use ($year, $by, $org) {
            $from = $year->starts_on->toDateString();
            $to = $year->ends_on->toDateString();
            $rows = $this->ledger->trialBalance($org, $from, $to, null, false, true)->filter(fn ($r) => in_array($r->type, ['revenue', 'expense'], true));
            $lines = [];
            $net = '0.00'; // credit-positive result
            foreach ($rows as $r) {
                $balance = bcsub($r->debit, $r->credit, 2); // this year's movement, debit-positive (earlier years were swept by their own closing)
                if (bccomp($balance, '0', 2) === 0) {
                    continue;
                }
                bccomp($balance, '0', 2) > 0 ? $lines[] = ['account' => $r->account_id, 'debit' => '0', 'credit' => $balance] : $lines[] = ['account' => $r->account_id, 'debit' => ltrim($balance, '-'), 'credit' => '0'];
                $net = bcadd($net, bcmul($balance, '-1', 2), 2);
            }
            $closing = null;
            if ($lines !== []) {
                $re = $this->chart->accountFor($org, 'retained_earnings')->id;
                bccomp($net, '0', 2) >= 0 ? $lines[] = ['account' => $re, 'debit' => '0', 'credit' => $net] : $lines[] = ['account' => $re, 'debit' => ltrim($net, '-'), 'credit' => '0'];
                $lines = array_values(array_filter($lines, fn ($l) => bccomp($l['debit'], '0', 2) > 0 || bccomp($l['credit'], '0', 2) > 0));
                $closing = $this->journals->postAuto($org, ['date' => $to, 'memo' => 'Closing entries '.$year->name, 'kind' => 'closing', 'source_type' => 'fiscal_year', 'source_id' => (string) $year->id, 'event_key' => 'closing'], $lines, $by, true);
            }
            Period::query()->where('fiscal_year_id', $year->id)->update(['status' => 'closed']);
            $year->update(['status' => 'closed', 'closed_by' => $by->id, 'closed_at' => now(), 'closing_journal_id' => $closing?->id]);
            $this->audit->log('FIN_YEAR_CLOSED', FiscalYear::class, $year->id, null, ['name' => $year->name, 'result' => $net]);

            return $year->refresh();
        });
    }
}
