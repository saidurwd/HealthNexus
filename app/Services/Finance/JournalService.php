<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Double-entry journals. A journal is saved only when its debits equal its credits; once posted it is never changed or deleted (the database refuses it too),
 * a mistake is undone by a reversal that points back. Manual journals need a second person; automatic ones come from the posting engine and post at once, once per source.
 */
class JournalService
{
    public function __construct(
        private readonly FiscalService $fiscal,
        private readonly FinancePolicy $policy,
        private readonly NumberSeriesService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Write a draft. $lines: each ['account' => id|code, 'debit' => '0.00', 'credit' => '0.00', 'cost_center' => id|code|null, 'party_type' => ?, 'party_id' => ?, 'memo' => ?, 'tax_code_id' => ?]
     *
     * @param  array{date:string,memo:string,reference?:?string,branch_id?:?int,kind?:string}  $data
     * @param  list<array<string,mixed>>  $lines
     */
    public function createDraft(int $organizationId, array $data, array $lines, ?User $by): Journal
    {
        return DB::transaction(function () use ($organizationId, $data, $lines, $by) {
            $journal = $this->make($organizationId, $data, 'draft', $by);
            $this->writeLines($journal, $lines);

            return $journal->refresh();
        });
    }

    /** Replace the lines of a draft (or of a journal sent back). */
    public function replaceLines(Journal $journal, array $lines, ?array $data = null): Journal
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft can be edited. A posted journal is corrected by a reversal.']);
        }

        return DB::transaction(function () use ($journal, $lines, $data) {
            JournalLine::query()->where('journal_id', $journal->id)->delete();
            if ($data) {
                $journal->update(array_filter(['journal_date' => $data['date'] ?? null, 'memo' => $data['memo'] ?? null, 'reference' => $data['reference'] ?? null]));
            }
            $this->writeLines($journal, $lines);

            return $journal->refresh();
        });
    }

    /** Send a draft for approval (or post it at once when the hospital turned maker-checker off). */
    public function submit(Journal $journal, User $by): Journal
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft can be submitted.']);
        }
        $this->assertReady($journal);
        if (! $this->policy->bool('journal.maker_checker', true)) {
            return $this->finalize($journal, $by, $by);
        }
        $journal->update(['status' => 'pending', 'submitted_at' => now()]);
        $this->audit->log('FIN_JOURNAL_SUBMITTED', Journal::class, $journal->id, null, ['number' => $journal->number]);

        return $journal->refresh();
    }

    public function approve(Journal $journal, User $by): Journal
    {
        if ($journal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only a journal waiting for approval can be approved.']);
        }
        if ($journal->created_by === $by->id && ! $this->policy->bool('journal.allow_self_approval', false)) {
            throw ValidationException::withMessages(['approver' => 'A journal must be approved by someone other than the person who wrote it.']);
        }

        return DB::transaction(function () use ($journal, $by) {
            $locked = Journal::query()->lockForUpdate()->findOrFail($journal->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This journal was already decided.']);
            }
            $this->assertReady($locked);

            return $this->finalize($locked, $by, $by);
        });
    }

    public function reject(Journal $journal, User $by, string $note): Journal
    {
        if ($journal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only a journal waiting for approval can be sent back.']);
        }
        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'Say what is wrong.']);
        }
        $journal->update(['status' => 'draft', 'reject_note' => mb_substr($note, 0, 250), 'submitted_at' => null]);
        $this->audit->log('FIN_JOURNAL_REJECTED', Journal::class, $journal->id, null, ['note' => $note, 'by' => $by->id]);

        return $journal->refresh();
    }

    /** A draft nobody wants is deleted; a posted one never is. */
    public function discard(Journal $journal): void
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft can be deleted.']);
        }
        $journal->delete();
    }

    /**
     * Post at once: the posting engine and system jobs. Idempotent per ($sourceType, $sourceId, $eventKey): a second call returns the first journal.
     *
     * @param  array{date:string,memo:string,reference?:?string,branch_id?:?int,kind?:string,source_type:string,source_id:string|int,event_key:string}  $data
     * @param  list<array<string,mixed>>  $lines
     */
    public function postAuto(int $organizationId, array $data, array $lines, ?User $by = null, bool $allowLocked = false): Journal
    {
        $key = ['organization_id' => $organizationId, 'source_type' => $data['source_type'], 'source_id' => (string) $data['source_id'], 'event_key' => $data['event_key']];
        $existing = Journal::query()->where($key)->first();
        if ($existing) {
            return $existing;
        }
        try {
            return DB::transaction(function () use ($organizationId, $data, $lines, $by, $allowLocked, $key) {
                $journal = $this->make($organizationId, $data + ['kind' => 'auto'], 'draft', $by, $allowLocked);
                $journal->update(['source_type' => $key['source_type'], 'source_id' => $key['source_id'], 'event_key' => $key['event_key']]);
                $this->writeLines($journal, $lines);
                $journal->refresh();
                $this->assertReady($journal, $allowLocked);

                return $this->finalize($journal, $by, null, $allowLocked);
            });
        } catch (QueryException $e) {
            $again = Journal::query()->where($key)->first(); // another worker posted the same source a moment ago
            if ($again) {
                return $again;
            }
            throw $e;
        }
    }

    /** Undo a posted journal with a new one that swaps debit and credit. The original stays, marked as reversed. */
    public function reverse(Journal $journal, ?User $by, string $reason, ?CarbonInterface $date = null): Journal
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why the journal is reversed.']);
        }

        return DB::transaction(function () use ($journal, $by, $reason, $date) {
            $original = Journal::query()->lockForUpdate()->findOrFail($journal->id);
            if ($original->status !== 'posted' || $original->reversed_by_id || $original->reversal_of_id) {
                throw ValidationException::withMessages(['status' => 'Only a posted journal that is not itself a reversal and was not reversed already can be reversed.']);
            }
            $day = ($date ?? CarbonImmutable::now())->toDateString();
            $reversal = $this->make($original->organization_id, ['date' => $day, 'memo' => mb_substr('Reversal of '.$original->number.': '.$reason, 0, 250), 'reference' => $original->number, 'branch_id' => $original->branch_id, 'kind' => 'reversal'], 'draft', $by);
            $reversal->update(['reversal_of_id' => $original->id]);
            $lines = JournalLine::query()->where('journal_id', $original->id)->orderBy('line_no')->get()->map(fn (JournalLine $l) => [
                'account' => $l->account_id, 'debit' => (string) $l->credit, 'credit' => (string) $l->debit, 'cost_center' => $l->cost_center_id, 'party_type' => $l->party_type, 'party_id' => $l->party_id, 'memo' => $l->memo, 'tax_code_id' => $l->tax_code_id,
            ])->all();
            $this->writeLines($reversal, $lines);
            $this->finalize($reversal->refresh(), $by, $by);
            $original->update(['reversed_by_id' => $reversal->id]);
            $this->audit->log('FIN_JOURNAL_REVERSED', Journal::class, $original->id, null, ['number' => $original->number, 'reversal' => $reversal->number, 'reason' => $reason]);

            return $reversal->refresh();
        });
    }

    // ---- internals ------------------------------------------------------------------------------------------------------------------------------

    /** @param array<string,mixed> $data */
    private function make(int $organizationId, array $data, string $status, ?User $by, bool $allowLocked = false): Journal
    {
        $date = CarbonImmutable::parse($data['date'])->toDateString();
        $period = $this->fiscal->periodFor($organizationId, $date);
        $number = $this->numbers->next('fin_journal', $organizationId, null, null, 'JV', '{PREFIX}-{YEAR}-{SEQ:6}', NumberSeriesService::PERIOD_YEAR, $by?->id);

        return Journal::create(['organization_id' => $organizationId, 'branch_id' => $data['branch_id'] ?? null, 'number' => $number, 'journal_date' => $date, 'period_id' => $period?->id, 'kind' => $data['kind'] ?? 'manual', 'status' => $status,
            'memo' => mb_substr((string) $data['memo'], 0, 250), 'reference' => isset($data['reference']) ? mb_substr((string) $data['reference'], 0, 80) : null, 'currency' => config('finance.currency', 'BDT'), 'created_by' => $by?->id]);
    }

    /** @param list<array<string,mixed>> $lines */
    private function writeLines(Journal $journal, array $lines): void
    {
        $max = $this->policy->int('journal.max_lines', 200);
        if (count($lines) < 2 || count($lines) > $max) {
            throw ValidationException::withMessages(['lines' => "A journal needs between 2 and $max lines."]);
        }
        $debit = $credit = '0.00';
        $rows = [];
        foreach ($lines as $i => $l) {
            $d = $this->amount($l['debit'] ?? 0, $i);
            $c = $this->amount($l['credit'] ?? 0, $i);
            if ((bccomp($d, '0', 2) > 0) === (bccomp($c, '0', 2) > 0)) {
                throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).' must have either a debit or a credit, not both and not neither.']);
            }
            $account = $this->account($journal->organization_id, $l['account'] ?? null, $i);
            $party = [$l['party_type'] ?? null, isset($l['party_id']) ? (int) $l['party_id'] : null];
            if (($party[0] === null) !== ($party[1] === null)) {
                throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).': give both the kind of party and which one, or neither.']);
            }
            if (in_array($account->control, ['receivable', 'payable'], true) && $party[0] === null) {
                throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).": {$account->code} {$account->name} is a control account; say whose it is (patient, corporate, insurer or supplier)."]);
            }
            $debit = bcadd($debit, $d, 2);
            $credit = bcadd($credit, $c, 2);
            $rows[] = ['journal_id' => $journal->id, 'organization_id' => $journal->organization_id, 'line_no' => $i + 1, 'journal_date' => $journal->journal_date->toDateString(), 'account_id' => $account->id, 'cost_center_id' => $this->costCenter($journal->organization_id, $l['cost_center'] ?? null),
                'party_type' => $party[0], 'party_id' => $party[1], 'debit' => $d, 'credit' => $c, 'memo' => isset($l['memo']) ? mb_substr((string) $l['memo'], 0, 200) : null, 'tax_code_id' => $l['tax_code_id'] ?? null];
        }
        JournalLine::query()->insert($rows);
        $journal->update(['total_debit' => $debit, 'total_credit' => $credit]);
    }

    private function amount(mixed $v, int $i): string
    {
        $s = is_string($v) ? trim($v) : (string) $v;
        if ($s === '' || ! is_numeric($s) || bccomp($s, '0', 2) < 0 || bccomp($s, '9999999999999999', 2) > 0) {
            throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).': the amount must be a number that is zero or more.']);
        }

        return bcadd($s, '0', 2);
    }

    private function account(int $organizationId, mixed $ref, int $i): Account
    {
        $q = Account::query()->where('organization_id', $organizationId);
        $account = is_int($ref) || (is_string($ref) && ctype_digit($ref) && ! $q->clone()->where('code', $ref)->exists()) ? $q->find((int) $ref) : $q->where('code', (string) $ref)->first();
        if ($account === null) {
            throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).': that account does not exist.']);
        }
        if (! $account->active || ! $account->postable) {
            throw ValidationException::withMessages(['lines' => 'Line '.($i + 1).": {$account->code} {$account->name} does not take postings (it is a heading or switched off)."]);
        }

        return $account;
    }

    private function costCenter(int $organizationId, mixed $ref): ?int
    {
        if ($ref === null || $ref === '') {
            return null;
        }
        $q = CostCenter::query()->where('organization_id', $organizationId)->where('active', true);
        $c = is_int($ref) ? $q->find($ref) : $q->where('code', (string) $ref)->first();
        if ($c === null) {
            throw ValidationException::withMessages(['lines' => 'A cost centre on the journal does not exist or is switched off.']);
        }

        return $c->id;
    }

    private function assertReady(Journal $journal, bool $allowLocked = false): void
    {
        if (bccomp((string) $journal->total_debit, (string) $journal->total_credit, 2) !== 0 || bccomp((string) $journal->total_debit, '0', 2) <= 0) {
            throw ValidationException::withMessages(['lines' => 'The journal does not balance: debit '.$journal->total_debit.', credit '.$journal->total_credit.'.']);
        }
        try {
            $this->fiscal->openPeriodFor($journal->organization_id, $journal->journal_date, $allowLocked);
        } catch (FinanceException $e) {
            throw ValidationException::withMessages(['date' => $e->getMessage()]);
        }
    }

    private function finalize(Journal $journal, ?User $by, ?User $approver, bool $allowLocked = false): Journal
    {
        $period = $this->fiscal->openPeriodFor($journal->organization_id, $journal->journal_date, $allowLocked);
        $journal->update(['status' => 'posted', 'posted_at' => now(), 'approved_by' => $approver?->id, 'period_id' => $period->id, 'reject_note' => null]);
        $this->audit->log('FIN_JOURNAL_POSTED', Journal::class, $journal->id, null, ['number' => $journal->number, 'kind' => $journal->kind, 'debit' => (string) $journal->total_debit]);

        return $journal->refresh();
    }
}
