<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLoan;
use App\Models\Hr\HrLoanEntry;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\Hr\HrPayrollAdjustment;
use App\Models\Hr\HrPayrollPeriod;
use App\Models\Hr\HrPayrollRun;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The payroll run (S09). A month has one regular run: it is created, calculated (as often as needed while it is still a draft — the answer only changes when an input
 * changes), then approved by someone other than the person who made it. Approval LOCKS the month: attendance, overtime, loans and adjustments are frozen into the run.
 * A mistake found later is never an edit: it is an adjustment, paid through a supplementary run.
 */
class PayrollService
{
    /** Findings that stop an approval; the rest are warnings shown on the exception dashboard. */
    private const BLOCKING = ['no_structure', 'negative_net', 'attendance_unprocessed'];

    public function __construct(private readonly PayrollCalculator $calculator, private readonly HrNumbers $numbers, private readonly PayrollJournalService $journals, private readonly AuditLogger $audit) {}

    public function openPeriod(int $organizationId, CarbonInterface $month): HrPayrollPeriod
    {
        return HrPayrollPeriod::query()->firstOrCreate(['organization_id' => $organizationId, 'month_start' => $month->copy()->startOfMonth()->toDateString()], ['month_end' => $month->copy()->endOfMonth()->toDateString()]);
    }

    public function createRun(User $by, HrPayrollPeriod $period, string $type = 'regular', ?string $reason = null): HrPayrollRun
    {
        if (! in_array($type, ['regular', 'supplementary'], true)) {
            throw ValidationException::withMessages(['run_type' => 'Use regular or supplementary.']);
        }
        if ($type === 'regular') {
            if ($period->month_end->gte(now()->startOfDay())) {
                throw ValidationException::withMessages(['period' => 'The month has not finished; payroll can be run after its last day.']);
            }
            if (HrPayrollRun::query()->where(['period_id' => $period->id, 'run_type' => 'regular'])->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['period' => 'This month already has a payroll run. Corrections after approval go in a supplementary run.']);
            }
        } else {
            if (! HrPayrollRun::query()->where(['period_id' => $period->id, 'run_type' => 'regular'])->whereIn('status', ['approved', 'paid'])->exists()) {
                throw ValidationException::withMessages(['period' => 'A supplementary run corrects a month whose regular run has been approved.']);
            }
            if (trim((string) $reason) === '') {
                throw ValidationException::withMessages(['reason' => 'Say what is being corrected.']);
            }
            if (HrPayrollRun::query()->where(['period_id' => $period->id, 'run_type' => 'supplementary'])->whereIn('status', ['draft', 'calculated'])->exists()) {
                throw ValidationException::withMessages(['period' => 'Finish the open supplementary run first.']);
            }
        }

        return HrPayrollRun::create([
            'organization_id' => $period->organization_id, 'period_id' => $period->id, 'number' => $this->numbers->payrollRun($period->organization_id, $period->month_start->format('Ym'), $type, $by), 'run_type' => $type, 'reason' => $reason, 'created_by' => $by->id,
        ]);
    }

    public function addAdjustment(HrEmployee $employee, HrPayrollPeriod $period, string $kind, string $name, string $amount, string $reason, User $by): HrPayrollAdjustment
    {
        if (! in_array($kind, ['earning', 'deduction'], true) || bccomp($amount, '0', 2) <= 0 || trim($reason) === '' || trim($name) === '') {
            throw ValidationException::withMessages(['adjustment' => 'Give a kind, a name, an amount above zero and a reason.']);
        }

        return HrPayrollAdjustment::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'period_id' => $period->id, 'kind' => $kind, 'name' => $name, 'amount' => $amount, 'reason' => $reason, 'created_by' => $by->id]);
    }

    /** (Re)calculates a run that has not been approved. Calling it again with unchanged inputs gives an identical result (same input hash). */
    public function calculate(HrPayrollRun $run): HrPayrollRun
    {
        return DB::transaction(function () use ($run) {
            $run = HrPayrollRun::query()->lockForUpdate()->findOrFail($run->id);
            if (! in_array($run->status, ['draft', 'calculated'], true)) {
                throw ValidationException::withMessages(['status' => "A {$run->status} run cannot be calculated again."]);
            }
            $period = HrPayrollPeriod::findOrFail($run->period_id);
            $run->lines()->each(fn ($l) => $l->items()->delete());
            $run->lines()->delete();

            $adjustments = HrPayrollAdjustment::query()->where(['period_id' => $period->id])->whereNull('run_id')->get()->groupBy('employee_id');
            $employees = $run->run_type === 'regular'
                ? HrEmployee::query()->where('organization_id', $run->organization_id)->whereNotNull('joined_on')->whereDate('joined_on', '<=', $period->month_end->toDateString())
                    ->where(fn ($q) => $q->whereNull('separated_on')->orWhereDate('separated_on', '>=', $period->month_start->toDateString()))->where('status', '!=', 'onboarding')->orderBy('id')->get()
                : HrEmployee::query()->whereIn('id', $adjustments->keys())->orderBy('id')->get();

            $totals = ['gross' => '0.00', 'deductions' => '0.00', 'tax' => '0.00', 'net' => '0.00', 'employer' => '0.00'];
            $fingerprint = [];
            $count = 0;
            foreach ($employees as $employee) {
                $mine = $adjustments->get($employee->id, collect());
                $result = $run->run_type === 'regular' ? $this->calculator->regular($employee, $period, $mine) : $this->calculator->supplementary($employee, $mine);
                if ($result === null) {
                    continue;
                }
                $line = $run->lines()->create(['organization_id' => $run->organization_id, 'employee_id' => $employee->id, 'gross' => $result['gross'], 'deductions' => $result['deductions'], 'tax' => $result['tax'], 'net' => $result['net'], 'employer' => $result['employer'], 'inputs' => $result['inputs'], 'exceptions' => $result['exceptions']]);
                foreach ($result['items'] as $item) {
                    $line->items()->create($item);
                }
                foreach (array_keys($totals) as $k) {
                    $totals[$k] = bcadd($totals[$k], $result[$k], 2);
                }
                $fingerprint[] = [$employee->id, $result['gross'], $result['deductions'], $result['tax'], $result['net'], $result['employer'], $result['inputs'], array_map(fn ($i) => [$i['code'], $i['kind'], $i['amount']], $result['items'])];
                $count++;
            }
            $run->update([
                'status' => 'calculated', 'calculation' => $run->calculation + 1, 'employee_count' => $count, 'total_gross' => $totals['gross'], 'total_deductions' => $totals['deductions'], 'total_tax' => $totals['tax'], 'total_net' => $totals['net'],
                'total_employer' => $totals['employer'], 'input_hash' => hash('sha256', json_encode($fingerprint)),
            ]);

            return $run->refresh();
        });
    }

    /** Findings across the run: the exception dashboard. @return array<string, list<array{employee: string, employee_no: string}>> */
    public function exceptions(HrPayrollRun $run): array
    {
        $out = [];
        foreach ($run->lines()->with('employee')->get() as $line) {
            foreach ((array) $line->exceptions as $code) {
                $out[$code][] = ['employee' => $line->employee->fullName(), 'employee_no' => $line->employee->employee_no];
            }
        }

        return $out;
    }

    public function approve(HrPayrollRun $run, User $by): HrPayrollRun
    {
        return DB::transaction(function () use ($run, $by) {
            $run = HrPayrollRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'calculated') {
                throw ValidationException::withMessages(['status' => 'Only a calculated run can be approved.']);
            }
            if ($run->created_by === $by->id) {
                throw ValidationException::withMessages(['approval' => 'A second person must approve payroll.']);
            }
            $blocking = collect($this->exceptions($run))->only(self::BLOCKING);
            if ($blocking->isNotEmpty()) {
                throw ValidationException::withMessages(['exceptions' => 'Resolve these first: '.$blocking->map(fn ($rows, $code) => str_replace('_', ' ', $code).' ('.count($rows).')')->implode(', ').'.']);
            }
            $period = HrPayrollPeriod::findOrFail($run->period_id);

            if ($run->run_type === 'regular') {
                // freeze what the run read
                HrAttendanceDay::query()->where('organization_id', $run->organization_id)->whereDate('work_date', '>=', $period->month_start->toDateString())->whereDate('work_date', '<=', $period->month_end->toDateString())->update(['locked' => true]);
                HrOvertimeEntry::query()->where('organization_id', $run->organization_id)->where('status', 'approved')->whereDate('work_date', '>=', $period->month_start->toDateString())->whereDate('work_date', '<=', $period->month_end->toDateString())->update(['status' => 'paid', 'payroll_run_id' => $run->id]);
                foreach ($run->lines()->with('items')->get() as $line) {
                    foreach ($line->items->where('code', 'LOAN') as $item) {
                        $loan = HrLoan::query()->where('public_id', $item->note)->lockForUpdate()->first();
                        if ($loan) {
                            $left = bcsub((string) $loan->balance, (string) $item->amount, 2);
                            $loan->update(['balance' => $left, 'status' => bccomp($left, '0', 2) <= 0 ? 'repaid' : 'active']);
                            HrLoanEntry::create(['loan_id' => $loan->id, 'amount' => bcmul((string) $item->amount, '-1', 2), 'entry_type' => 'deduction', 'payroll_run_id' => $run->id, 'created_at' => now()]);
                        }
                    }
                }
                HrPayrollAdjustment::query()->where('period_id', $period->id)->whereNull('run_id')->update(['run_id' => $run->id]);
                $period->update(['status' => 'closed']);
            } else {
                HrPayrollAdjustment::query()->where('period_id', $period->id)->whereNull('run_id')->update(['run_id' => $run->id]);
            }
            $run->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now(), 'journal' => $this->journals->build($run)]);
            $this->audit->log('PAYROLL_APPROVED', HrPayrollRun::class, $run->id, null, ['number' => $run->number, 'net' => (string) $run->total_net, 'employees' => $run->employee_count]);

            return $run->refresh();
        });
    }

    public function cancel(HrPayrollRun $run, string $reason): HrPayrollRun
    {
        if (! in_array($run->status, ['draft', 'calculated'], true)) {
            throw ValidationException::withMessages(['status' => 'An approved run cannot be cancelled; correct it with a supplementary run.']);
        }
        $run->update(['status' => 'cancelled', 'reason' => mb_substr(trim(($run->reason ? $run->reason.' · ' : '').'Cancelled: '.$reason), 0, 300)]);

        return $run->refresh();
    }

    public function publishPayslips(HrPayrollRun $run): HrPayrollRun
    {
        if (! in_array($run->status, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages(['status' => 'Payslips are published after approval.']);
        }
        $run->update(['payslips_published_at' => $run->payslips_published_at ?? now()]);

        return $run->refresh();
    }

    public function markPaid(HrPayrollRun $run): HrPayrollRun
    {
        if ($run->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only an approved run can be marked paid.']);
        }
        $run->update(['status' => 'paid']);

        return $run->refresh();
    }

    /**
     * Reconciliation (spec 106): the run's totals, its lines, its line items, its payment files and its journal must all say the same thing.
     *
     * @return array<string, bool>
     */
    public function reconcile(HrPayrollRun $run): array
    {
        $lines = $run->lines()->with('items')->get();
        $sum = fn (string $f) => $lines->reduce(fn ($c, $l) => bcadd($c, (string) $l->{$f}, 2), '0.00');
        $itemSum = fn (string $kind) => $lines->flatMap->items->where('kind', $kind)->reduce(fn ($c, $i) => bcadd($c, (string) $i->amount, 2), '0.00');
        $checks = [
            'lines_equal_run_gross' => bccomp($sum('gross'), (string) $run->total_gross, 2) === 0,
            'lines_equal_run_net' => bccomp($sum('net'), (string) $run->total_net, 2) === 0,
            'earnings_equal_gross' => bccomp($itemSum('earning'), (string) $run->total_gross, 2) === 0,
            'deductions_equal' => bccomp($itemSum('deduction'), (string) $run->total_deductions, 2) === 0,
            'tax_equal' => bccomp($itemSum('tax'), (string) $run->total_tax, 2) === 0,
            'gross_minus_deductions_minus_tax_equals_net' => bccomp(bcsub(bcsub((string) $run->total_gross, (string) $run->total_deductions, 2), (string) $run->total_tax, 2), (string) $run->total_net, 2) === 0,
        ];
        if ($run->journal) {
            $debit = array_reduce($run->journal['lines'], fn ($c, $l) => bcadd($c, $l['debit'], 2), '0.00');
            $credit = array_reduce($run->journal['lines'], fn ($c, $l) => bcadd($c, $l['credit'], 2), '0.00');
            $checks['journal_balances'] = bccomp($debit, $credit, 2) === 0;
        }
        $paid = DB::table('hr_payment_batches')->where('run_id', $run->id)->sum('total');
        if ($paid > 0) {
            $payable = $lines->filter(fn ($l) => bccomp((string) $l->net, '0', 2) > 0 && ! in_array('no_payment_account', (array) $l->exceptions, true))->reduce(fn ($c, $l) => bcadd($c, (string) $l->net, 2), '0.00');
            $checks['payment_files_equal_payable_net'] = bccomp(bcadd((string) $paid, '0', 2), $payable, 2) === 0;
        }

        return $checks;
    }
}
