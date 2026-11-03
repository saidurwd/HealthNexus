<?php

namespace App\Services\Hr;

use App\Contracts\Integration\FinanceGateway;
use App\Models\Hr\HrPayrollRun;
use Illuminate\Validation\ValidationException;

/** The accounting hand-off (S10): one balanced journal per approved run, posted through FinanceGateway (null adapter until Phase 21). Account codes are settings. */
class PayrollJournalService
{
    public function __construct(private readonly FinanceGateway $finance, private readonly HrPolicy $policy) {}

    /** @return array{lines: list<array{account: string, debit: string, credit: string, memo: string}>, total: string} */
    public function build(HrPayrollRun $run): array
    {
        $accounts = array_merge(['expense' => 'SALARY_EXPENSE', 'payable' => 'SALARY_PAYABLE', 'tax' => 'TAX_PAYABLE', 'pf' => 'PF_PAYABLE', 'loan' => 'LOAN_RECEIVABLE', 'other' => 'OTHER_DEDUCTIONS_PAYABLE'], (array) $this->policy->get('journal_accounts', []));
        $items = $run->lines()->with('items')->get()->flatMap->items;
        $sum = fn ($filter) => $items->filter($filter)->reduce(fn ($c, $i) => bcadd($c, (string) $i->amount, 2), '0.00');
        $employer = $sum(fn ($i) => $i->kind === 'employer');
        $pfEmployee = $sum(fn ($i) => $i->kind === 'deduction' && $i->code === 'PF');
        $loans = $sum(fn ($i) => $i->kind === 'deduction' && $i->code === 'LOAN');
        $deductions = $sum(fn ($i) => $i->kind === 'deduction');
        $other = bcsub(bcsub($deductions, $pfEmployee, 2), $loans, 2);
        $expense = bcadd((string) $run->total_gross, $employer, 2);
        $line = fn (string $account, string $debit, string $credit, string $memo) => ['account' => $accounts[$account], 'debit' => $debit, 'credit' => $credit, 'memo' => $memo];
        $lines = array_values(array_filter([
            $line('expense', $expense, '0.00', "Salary and employer contributions {$run->number}"),
            $line('payable', '0.00', (string) $run->total_net, 'Net salary payable'),
            bccomp((string) $run->total_tax, '0', 2) > 0 ? $line('tax', '0.00', (string) $run->total_tax, 'Income tax withheld') : null,
            bccomp(bcadd($pfEmployee, $employer, 2), '0', 2) > 0 ? $line('pf', '0.00', bcadd($pfEmployee, $employer, 2), 'Provident fund (employee and employer)') : null,
            bccomp($loans, '0', 2) > 0 ? $line('loan', '0.00', $loans, 'Loan and advance repayments') : null,
            bccomp($other, '0', 2) > 0 ? $line('other', '0.00', $other, 'Other deductions') : null,
        ]));
        $debit = array_reduce($lines, fn ($c, $l) => bcadd($c, $l['debit'], 2), '0.00');
        $credit = array_reduce($lines, fn ($c, $l) => bcadd($c, $l['credit'], 2), '0.00');
        if (bccomp($debit, $credit, 2) !== 0) {
            throw ValidationException::withMessages(['journal' => "The journal does not balance (debit $debit, credit $credit); payroll cannot be approved."]);
        }

        return ['lines' => $lines, 'total' => $debit];
    }

    /** @return bool true when Finance took it (the null adapter never does; the journal then stays on the run for Phase 21) */
    public function post(HrPayrollRun $run): bool
    {
        if (! in_array($run->status, ['approved', 'paid'], true) || ! $run->journal) {
            throw ValidationException::withMessages(['status' => 'Only an approved run has a journal.']);
        }
        if ($run->finance_reference) {
            return true;
        }
        $result = $this->finance->postJournal(['organization_id' => $run->organization_id, 'source' => 'hr:payroll:'.$run->public_id, 'date' => $run->approved_at?->toDateString(), 'memo' => 'Payroll '.$run->number, 'lines' => $run->journal['lines']]);
        $result->handled && $run->update(['finance_reference' => (string) ($result->data['reference'] ?? 'POSTED')]);

        return $result->handled;
    }
}
