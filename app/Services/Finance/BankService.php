<?php

namespace App\Services\Finance;

use App\Models\Billing\BillingCashierSession;
use App\Models\Finance\Account;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankDeposit;
use App\Models\Finance\BankStatement;
use App\Models\Finance\BankStatementLine;
use App\Models\Finance\Journal;
use App\Models\Finance\SupplierPayment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Cash, bank, mobile wallets and gateway settlements: tills banked, transfers, bank statements matched line by line. */
class BankService
{
    public function __construct(
        private readonly ChartService $chart,
        private readonly JournalService $journals,
        private readonly LedgerService $ledger,
        private readonly NumberSeriesService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    public function createAccount(int $organizationId, string $name, string $kind, int $ledgerAccountId, ?string $bankName = null, ?string $last4 = null, ?string $methodCode = null): BankAccount
    {
        if (! in_array($kind, ['bank', 'cash', 'wallet', 'gateway'], true)) {
            throw ValidationException::withMessages(['kind' => 'Choose bank, cash, wallet or gateway.']);
        }
        $account = Account::query()->where('organization_id', $organizationId)->where('active', true)->where('postable', true)->find($ledgerAccountId);
        if ($account === null || $account->type !== 'asset') {
            throw ValidationException::withMessages(['account_id' => 'Choose an asset account that takes postings.']);
        }
        if ($last4 !== null && ! preg_match('/^\d{0,4}$/', $last4)) {
            throw ValidationException::withMessages(['number_last4' => 'Keep only the last four digits; the full number is never stored.']);
        }

        return BankAccount::create(['organization_id' => $organizationId, 'account_id' => $account->id, 'name' => $name, 'kind' => $kind, 'bank_name' => $bankName, 'number_last4' => $last4, 'payment_method_code' => $methodCode]);
    }

    /** What the books say the account holds on a date. */
    public function ledgerBalance(BankAccount $bank, ?string $asOf = null): string
    {
        return $this->ledger->balance($bank->organization_id, Account::query()->findOrFail($bank->account_id), $asOf);
    }

    // ---- tills banked ---------------------------------------------------------------------------------------------------------------------------

    /**
     * Bank what a closed cashier session counted. Any difference between the count and what the system expected goes to cash over/short first, so the book cash equals the real cash.
     */
    public function depositSession(BillingCashierSession $session, BankAccount $bank, User $by, ?string $amount = null, ?string $reference = null): BankDeposit
    {
        $org = $session->organization_id;
        if ($session->status !== 'closed') {
            throw ValidationException::withMessages(['session' => 'Close the cashier session before banking it.']);
        }
        if (BankDeposit::query()->where('cashier_session_id', $session->id)->exists()) {
            throw ValidationException::withMessages(['session' => 'This session was already banked.']);
        }
        $amount ??= bcsub((string) $session->actual_closing, (string) $session->opening_balance, 2); // the float stays in the till
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'There is nothing to bank.']);
        }
        $till = $this->chart->accountFor($org, 'cash');
        $date = ($session->closed_at ?? now())->toDateString();

        return DB::transaction(function () use ($session, $bank, $by, $amount, $reference, $org, $till, $date) {
            $variance = (string) $session->variance;
            if (bccomp($variance, '0', 2) !== 0) {
                $over = $this->chart->accountFor($org, 'cash_over_short');
                $abs = ltrim($variance, '-');
                $lines = bccomp($variance, '0', 2) < 0
                    ? [['account' => $over->id, 'debit' => $abs, 'credit' => '0'], ['account' => $till->id, 'debit' => '0', 'credit' => $abs]]
                    : [['account' => $till->id, 'debit' => $abs, 'credit' => '0'], ['account' => $over->id, 'debit' => '0', 'credit' => $abs]];
                $this->journals->postAuto($org, ['date' => $date, 'memo' => 'Till difference, cashier session #'.$session->id, 'source_type' => 'cashier_session', 'source_id' => (string) $session->id, 'event_key' => 'variance', 'branch_id' => $session->branch_id], $lines, $by);
            }

            return $this->deposit($org, $bank, $till->id, $date, $amount, $by, $reference ?: 'Session #'.$session->id, $session->id, $session->branch_id);
        });
    }

    /** Move money from a till or wallet account to a bank account. */
    public function transfer(int $organizationId, BankAccount $from, BankAccount $to, string $amount, string $date, User $by, ?string $reference = null): BankDeposit
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['to' => 'Choose a different account to move the money to.']);
        }
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be above zero.']);
        }

        return $this->deposit($organizationId, $to, $from->account_id, $date, $amount, $by, $reference, null, null, $from);
    }

    private function deposit(int $org, BankAccount $bank, int $fromLedgerAccount, string $date, string $amount, User $by, ?string $reference, ?int $sessionId, ?int $branchId, ?BankAccount $fromBank = null): BankDeposit
    {
        $number = $this->numbers->next('fin_deposit', $org, null, null, 'DEP', '{PREFIX}-{YEAR}-{SEQ:6}', NumberSeriesService::PERIOD_YEAR, $by->id);
        $journal = $this->journals->postAuto($org, ['date' => $date, 'memo' => 'Bank deposit '.$number, 'reference' => $reference, 'branch_id' => $branchId, 'source_type' => 'bank_deposit', 'source_id' => $number, 'event_key' => 'banked'], [
            ['account' => $bank->account_id, 'debit' => $amount, 'credit' => '0', 'party_type' => 'bank', 'party_id' => $bank->id],
            ['account' => $fromLedgerAccount, 'debit' => '0', 'credit' => $amount] + ($fromBank ? ['party_type' => 'bank', 'party_id' => $fromBank->id] : []),
        ], $by);
        $deposit = BankDeposit::create(['organization_id' => $org, 'number' => $number, 'bank_account_id' => $bank->id, 'from_account_id' => $fromBank?->id, 'cashier_session_id' => $sessionId, 'deposit_date' => $date, 'amount' => $amount, 'reference' => $reference,
            'status' => 'banked', 'created_by' => $by->id, 'journal_id' => $journal->id]);
        $this->audit->log('FIN_DEPOSIT', BankDeposit::class, $deposit->id, null, ['number' => $number, 'amount' => $amount]);

        return $deposit;
    }

    /**
     * A payment gateway's settlement file arrived in the bank: the gateway clearing account is cleared, the fee is charged, the rest lands in the bank.
     */
    public function settleGateway(int $organizationId, int $settlementId, BankAccount $bank, User $by): Journal
    {
        $row = DB::table('portal_settlements')->where('organization_id', $organizationId)->where('id', $settlementId)->first();
        if ($row === null) {
            throw ValidationException::withMessages(['settlement' => 'That settlement file does not exist.']);
        }
        $total = bcadd((string) $row->total, '0', 2);
        $fees = bcadd((string) $row->fees, '0', 2);
        $net = bcsub($total, $fees, 2);
        $lines = [['account' => $bank->account_id, 'debit' => $net, 'credit' => '0', 'party_type' => 'bank', 'party_id' => $bank->id]];
        bccomp($fees, '0', 2) > 0 && $lines[] = ['account' => $this->chart->accountFor($organizationId, 'bank_charges')->id, 'debit' => $fees, 'credit' => '0'];
        $lines[] = ['account' => $this->chart->accountFor($organizationId, 'gateway_clearing')->id, 'debit' => '0', 'credit' => $total];

        return $this->journals->postAuto($organizationId, ['date' => CarbonImmutable::parse($row->period_to)->toDateString(), 'memo' => 'Settlement '.($row->file_name ?: '#'.$row->id), 'source_type' => 'portal_settlement', 'source_id' => (string) $row->id, 'event_key' => 'banked'], $lines, $by);
    }

    // ---- bank statements -------------------------------------------------------------------------------------------------------------------------

    /**
     * Import a statement from CSV text: date, description, reference, money in, money out (header row allowed). Lines already imported for the same account are skipped.
     *
     * @return array{statement:BankStatement,imported:int,duplicates:int}
     */
    public function importStatement(BankAccount $bank, string $csv, ?string $fileName, User $by, ?string $openingBalance = null, ?string $closingBalance = null): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($csv)) as $i => $raw) {
            if (trim($raw) === '') {
                continue;
            }
            $c = str_getcsv($raw);
            $date = $this->date($c[0] ?? '');
            if ($date === null) {
                if ($i === 0) {
                    continue; // header
                }
                throw ValidationException::withMessages(['file' => 'Line '.($i + 1).' does not start with a date.']);
            }
            $in = $this->money($c[3] ?? '0');
            $out = $this->money($c[4] ?? '0');
            if ((bccomp($in, '0', 2) > 0) === (bccomp($out, '0', 2) > 0)) {
                throw ValidationException::withMessages(['file' => 'Line '.($i + 1).' must have money in or money out, not both and not neither.']);
            }
            $rows[] = ['date' => $date, 'description' => mb_substr(trim($c[1] ?? ''), 0, 250), 'reference' => mb_substr(trim($c[2] ?? ''), 0, 80), 'in' => $in, 'out' => $out];
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no transaction lines.']);
        }
        $dates = array_column($rows, 'date');

        return DB::transaction(function () use ($bank, $rows, $dates, $fileName, $by, $openingBalance, $closingBalance) {
            $statement = BankStatement::create(['organization_id' => $bank->organization_id, 'bank_account_id' => $bank->id, 'file_name' => $fileName, 'period_from' => min($dates), 'period_to' => max($dates), 'opening_balance' => $openingBalance, 'closing_balance' => $closingBalance, 'imported_by' => $by->id, 'status' => 'open']);
            $imported = $dupes = 0;
            $occurrence = [];
            foreach ($rows as $r) {
                // two identical lines on one day (two bank fees) are two lines: the hash includes which one of the identical lines this is
                $base = implode('|', [$bank->id, $r['date'], $r['description'], $r['reference'], $r['in'], $r['out']]);
                $occurrence[$base] = ($occurrence[$base] ?? 0) + 1;
                $hash = hash('sha256', $base.'|'.$occurrence[$base]);
                $seen = BankStatementLine::query()->where('line_hash', $hash)->whereIn('statement_id', BankStatement::query()->where('bank_account_id', $bank->id)->select('id'))->exists();
                if ($seen) {
                    $dupes++;

                    continue;
                }
                BankStatementLine::create(['statement_id' => $statement->id, 'txn_date' => $r['date'], 'description' => $r['description'], 'reference' => $r['reference'], 'money_in' => $r['in'], 'money_out' => $r['out'], 'line_hash' => $hash]);
                $imported++;
            }
            $statement->update(['line_count' => $imported]);

            return ['statement' => $statement, 'imported' => $imported, 'duplicates' => $dupes];
        });
    }

    private function date(string $v): ?string
    {
        $v = trim($v);
        if ($v === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$|^\d{2}[\/-]\d{2}[\/-]\d{4}$/', $v)) {
            return null;
        }
        try {
            return preg_match('/^\d{4}/', $v) ? CarbonImmutable::parse($v)->toDateString() : CarbonImmutable::createFromFormat('d/m/Y', str_replace('-', '/', $v))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(string $v): string
    {
        $v = str_replace([',', ' '], '', trim($v));

        return $v === '' || ! is_numeric($v) ? '0.00' : bcadd(ltrim($v, '-'), '0', 2);
    }

    /** Suggest matches: money in ↔ deposits of this account; money out ↔ supplier payments from it. Same amount within three days. Nothing is matched without a person (or the auto flag). */
    public function autoMatch(BankStatement $statement, ?User $by = null): int
    {
        $bank = BankAccount::query()->findOrFail($statement->bank_account_id);
        $n = 0;
        foreach (BankStatementLine::query()->where(['statement_id' => $statement->id, 'status' => 'unmatched'])->get() as $line) {
            $from = CarbonImmutable::parse($line->txn_date)->subDays(3)->toDateString();
            $to = CarbonImmutable::parse($line->txn_date)->addDays(3)->toDateString();
            $match = null;
            if (bccomp((string) $line->money_in, '0', 2) > 0) {
                $landed = DB::table('fin_journals')->where(['organization_id' => $bank->organization_id, 'source_type' => 'portal_settlement'])->whereBetween('journal_date', [$from, $to])->pluck('source_id')->map(fn ($v) => (int) $v)->all();
                $settlement = $landed === [] ? null : DB::table('portal_settlements')->where('organization_id', $bank->organization_id)->whereIn('id', $landed)->whereRaw('(total - fees) = ?', [$line->money_in])
                    ->whereNotIn('id', BankStatementLine::query()->where('matched_type', 'settlement')->whereNotNull('matched_id')->select('matched_id'))->first(['id']);
                if ($settlement) {
                    $line->update(['status' => 'matched', 'matched_type' => 'settlement', 'matched_id' => $settlement->id, 'matched_by' => $by?->id, 'matched_at' => now()]);
                    $n++;

                    continue;
                }
                $match = BankDeposit::query()->where(['organization_id' => $bank->organization_id, 'bank_account_id' => $bank->id, 'status' => 'banked'])->whereBetween('deposit_date', [$from, $to])->where('amount', $line->money_in)
                    ->whereNotIn('id', BankStatementLine::query()->where('matched_type', 'deposit')->whereNotNull('matched_id')->select('matched_id'))->first();
                $type = 'deposit';
            } else {
                $match = SupplierPayment::query()->where(['organization_id' => $bank->organization_id, 'bank_account_id' => $bank->id, 'status' => 'paid'])->whereBetween('payment_date', [$from, $to])->where('net_amount', $line->money_out)
                    ->whereNotIn('id', BankStatementLine::query()->where('matched_type', 'supplier_payment')->whereNotNull('matched_id')->select('matched_id'))->first();
                $type = 'supplier_payment';
            }
            if ($match) {
                $line->update(['status' => 'matched', 'matched_type' => $type, 'matched_id' => $match->id, 'matched_by' => $by?->id, 'matched_at' => now()]);
                $n++;
            }
        }

        return $n;
    }

    /** Post a statement line the books do not know yet (bank charge, interest, a deposit nobody recorded) against an account the accountant chooses. */
    public function postLine(BankStatementLine $line, string $otherAccountCode, User $by, ?string $note = null): Journal
    {
        if ($line->status !== 'unmatched') {
            throw ValidationException::withMessages(['line' => 'This line is already dealt with.']);
        }
        $statement = BankStatement::query()->findOrFail($line->statement_id);
        $bank = BankAccount::query()->findOrFail($statement->bank_account_id);
        $org = $bank->organization_id;
        $other = Account::query()->where(['organization_id' => $org, 'code' => $otherAccountCode, 'active' => true, 'postable' => true])->first();
        if ($other === null) {
            throw ValidationException::withMessages(['account' => 'Choose an account that takes postings.']);
        }
        $in = bccomp((string) $line->money_in, '0', 2) > 0;
        $amount = $in ? (string) $line->money_in : (string) $line->money_out;
        $lines = $in
            ? [['account' => $bank->account_id, 'debit' => $amount, 'credit' => '0', 'party_type' => 'bank', 'party_id' => $bank->id], ['account' => $other->id, 'debit' => '0', 'credit' => $amount]]
            : [['account' => $other->id, 'debit' => $amount, 'credit' => '0'], ['account' => $bank->account_id, 'debit' => '0', 'credit' => $amount, 'party_type' => 'bank', 'party_id' => $bank->id]];

        return DB::transaction(function () use ($org, $line, $lines, $by, $note) {
            $journal = $this->journals->postAuto($org, ['date' => $line->txn_date->toDateString(), 'memo' => 'From bank statement: '.($line->description ?: 'line '.$line->id), 'reference' => $line->reference, 'source_type' => 'bank_line', 'source_id' => (string) $line->id, 'event_key' => 'posted'], $lines, $by);
            $line->update(['status' => 'matched', 'matched_type' => 'journal', 'matched_id' => $journal->id, 'matched_by' => $by->id, 'matched_at' => now(), 'note' => $note]);

            return $journal;
        });
    }

    public function ignoreLine(BankStatementLine $line, User $by, string $note): void
    {
        if ($line->status !== 'unmatched' || trim($note) === '') {
            throw ValidationException::withMessages(['line' => 'Only an open line can be set aside, and a reason is needed.']);
        }
        $line->update(['status' => 'ignored', 'matched_by' => $by->id, 'matched_at' => now(), 'note' => mb_substr($note, 0, 250)]);
    }

    /**
     * Where the statement and the books stand: the statement closing balance, the ledger balance on the same date and what is still unmatched. A reconciled statement has nothing unmatched and the two balances agree.
     *
     * @return array{statement_closing:?string,ledger:string,difference:?string,unmatched_in:string,unmatched_out:string,unmatched:int,reconciled:bool}
     */
    public function summary(BankStatement $statement): array
    {
        $bank = BankAccount::query()->findOrFail($statement->bank_account_id);
        $ledger = $this->ledgerBalance($bank, $statement->period_to->toDateString());
        $open = BankStatementLine::query()->where(['statement_id' => $statement->id, 'status' => 'unmatched']);
        $in = (string) $open->clone()->sum('money_in');
        $out = (string) $open->clone()->sum('money_out');
        $closing = $statement->closing_balance !== null ? (string) $statement->closing_balance : null;
        $difference = $closing !== null ? bcsub($closing, $ledger, 2) : null;

        return ['statement_closing' => $closing, 'ledger' => $ledger, 'difference' => $difference, 'unmatched_in' => bcadd($in, '0', 2), 'unmatched_out' => bcadd($out, '0', 2), 'unmatched' => $open->count(),
            'reconciled' => $open->count() === 0 && ($difference === null || bccomp($difference, '0', 2) === 0)];
    }

    public function markReconciled(BankStatement $statement, User $by): BankStatement
    {
        $s = $this->summary($statement);
        if (! $s['reconciled']) {
            throw ValidationException::withMessages(['statement' => 'Lines are still unmatched or the balance differs from the books.']);
        }
        $statement->update(['status' => 'reconciled']);
        $this->audit->log('FIN_STATEMENT_RECONCILED', BankStatement::class, $statement->id, null, ['by' => $by->id]);

        return $statement->refresh();
    }
}
