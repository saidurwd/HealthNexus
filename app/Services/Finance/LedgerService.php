<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Reads the books: balances, the trial balance and an account's ledger. Only posted journals count. */
class LedgerService
{
    /** @return Builder */
    private function posted(int $organizationId)
    {
        return DB::table('fin_journal_lines as l')->join('fin_journals as j', 'j.id', '=', 'l.journal_id')->where('l.organization_id', $organizationId)->where('j.status', 'posted');
    }

    /**
     * Debit and credit totals per account for a date range, with the balance carried in from before the range.
     *
     * @return Collection<int, \stdClass> account_id, code, name, type, normal_side, opening, debit, credit, closing
     */
    public function trialBalance(int $organizationId, ?string $from, string $to, ?int $costCenterId = null, bool $includeZero = false, bool $excludeClosing = false): Collection
    {
        $from ??= '0001-01-01';
        $rows = $this->posted($organizationId)->join('fin_accounts as a', 'a.id', '=', 'l.account_id')
            ->when($costCenterId, fn ($q) => $q->where('l.cost_center_id', $costCenterId))
            ->when($excludeClosing, fn ($q) => $q->where('j.kind', '<>', 'closing'))
            ->where('l.journal_date', '<=', $to)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_side')
            ->orderBy('a.code')
            ->selectRaw('a.id as account_id, a.code, a.name, a.type, a.normal_side,
                coalesce(sum(case when l.journal_date < ? then l.debit - l.credit else 0 end), 0) as open_net,
                coalesce(sum(case when l.journal_date >= ? then l.debit else 0 end), 0) as debit,
                coalesce(sum(case when l.journal_date >= ? then l.credit else 0 end), 0) as credit', [$from, $from, $from])
            ->get();

        return $rows->map(function ($r) {
            $openNet = (string) $r->open_net;
            $closeNet = bcadd(bcsub((string) $r->debit, (string) $r->credit, 2), $openNet, 2);
            $debitSide = $r->normal_side === 'debit';
            $r->opening = $debitSide ? bcadd($openNet, '0', 2) : bcmul($openNet, '-1', 2);
            $r->closing = $debitSide ? $closeNet : bcmul($closeNet, '-1', 2);
            $r->debit = bcadd((string) $r->debit, '0', 2);
            $r->credit = bcadd((string) $r->credit, '0', 2);
            unset($r->open_net);

            return $r;
        })->filter(fn ($r) => $includeZero || bccomp($r->opening, '0', 2) !== 0 || bccomp($r->debit, '0', 2) !== 0 || bccomp($r->credit, '0', 2) !== 0)->values();
    }

    /** @return array{debit:string,credit:string,balanced:bool} totals of a trial balance: in a sound set of books the closing debits equal the closing credits. */
    public function totals(Collection $trialBalance): array
    {
        $dr = $cr = '0.00';
        foreach ($trialBalance as $r) {
            $net = $r->normal_side === 'debit' ? $r->closing : bcmul($r->closing, '-1', 2); // debit-positive
            if (bccomp($net, '0', 2) >= 0) {
                $dr = bcadd($dr, $net, 2);
            } else {
                $cr = bcadd($cr, bcmul($net, '-1', 2), 2);
            }
        }

        return ['debit' => $dr, 'credit' => $cr, 'balanced' => bccomp($dr, $cr, 2) === 0];
    }

    /** Balance of one account on a date, on its normal side. */
    public function balance(int $organizationId, Account $account, ?string $asOf = null): string
    {
        $r = $this->posted($organizationId)->where('l.account_id', $account->id)->when($asOf, fn ($q) => $q->where('l.journal_date', '<=', $asOf))->selectRaw('coalesce(sum(l.debit), 0) as d, coalesce(sum(l.credit), 0) as c')->first();
        $net = bcsub((string) $r->d, (string) $r->c, 2);

        return $account->normal_side === 'debit' ? $net : bcmul($net, '-1', 2);
    }

    /**
     * An account's ledger: every line in the range with a running balance on the account's normal side.
     *
     * @return array{opening:string,lines:Collection<int,\stdClass>,closing:string}
     */
    public function accountLedger(int $organizationId, Account $account, string $from, string $to, ?int $costCenterId = null): array
    {
        $sign = $account->normal_side === 'debit' ? '1' : '-1';
        $open = $this->posted($organizationId)->where('l.account_id', $account->id)->where('l.journal_date', '<', $from)->when($costCenterId, fn ($q) => $q->where('l.cost_center_id', $costCenterId))->selectRaw('coalesce(sum(l.debit - l.credit), 0) as net')->value('net');
        $running = bcmul((string) $open, $sign, 2);
        $opening = $running;
        $lines = $this->posted($organizationId)->where('l.account_id', $account->id)->whereBetween('l.journal_date', [$from, $to])->when($costCenterId, fn ($q) => $q->where('l.cost_center_id', $costCenterId))
            ->orderBy('l.journal_date')->orderBy('j.id')->orderBy('l.line_no')
            ->select('l.journal_date', 'l.debit', 'l.credit', 'l.memo as line_memo', 'l.party_type', 'l.party_id', 'j.number', 'j.memo', 'j.public_id', 'j.kind')->get()
            ->map(function ($r) use (&$running, $sign) {
                $running = bcadd($running, bcmul(bcsub((string) $r->debit, (string) $r->credit, 2), $sign, 2), 2);
                $r->balance = $running;

                return $r;
            });

        return ['opening' => $opening, 'lines' => $lines, 'closing' => $running];
    }

    public function monthEnd(string $date): string
    {
        return CarbonImmutable::parse($date)->endOfMonth()->toDateString();
    }
}
