<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrLoan;
use App\Models\Hr\HrPayrollAdjustment;
use App\Models\Hr\HrPayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * One person's pay for one month. Pure arithmetic over recorded inputs — attendance, approved leave, approved overtime, the salary structure in force, loans,
 * the tax slabs — with no clock and no randomness, so the same inputs always give the same result (spec 106, 107). Every rate comes from a structure, a slab or a
 * setting. Money is bcmath strings rounded half-up to the paisa.
 *
 * Example rules, all to be confirmed (OD-16): pay is divided over the calendar days of the month; days before joining or after leaving are not paid; an absent day
 * or unpaid leave day is not paid; a half day counts as half a day unpaid; weekly off and holidays are paid; PF is a share of basic; tax is the monthly share of the
 * annual tax on the taxable earnings of the month, annualized (a simplification — the annual true-up belongs to the accountant).
 */
class PayrollCalculator
{
    public function __construct(private readonly CompensationService $compensation, private readonly OvertimeService $overtime, private readonly HrPolicy $policy) {}

    /**
     * @param  Collection<int, HrPayrollAdjustment>  $adjustments  pending corrections for this person
     * @return array{gross: string, deductions: string, tax: string, net: string, employer: string, items: list<array<string, string>>, inputs: array<string, mixed>, exceptions: list<string>}|null null when the person has nothing to be paid for this month
     */
    public function regular(HrEmployee $employee, HrPayrollPeriod $period, Collection $adjustments): ?array
    {
        $start = CarbonImmutable::parse($period->month_start->toDateString());
        $end = CarbonImmutable::parse($period->month_end->toDateString());
        $from = $employee->joined_on && $employee->joined_on->gt($start) ? CarbonImmutable::parse($employee->joined_on->toDateString()) : $start;
        $to = $employee->separated_on && $employee->separated_on->lt($end) ? CarbonImmutable::parse($employee->separated_on->toDateString()) : $end;
        if (! $employee->joined_on || $from->gt($to) || $employee->joined_on->gt($end)) {
            return null;
        }
        $daysInMonth = $start->daysInMonth;
        $employedDays = (int) $from->diffInDays($to) + 1;
        $exceptions = [];
        $items = [];

        $structure = $this->compensation->structureOn($employee, $from); // the pay in force on the first day the person was employed this month
        if (! $structure) {
            return ['gross' => '0.00', 'deductions' => '0.00', 'tax' => '0.00', 'net' => '0.00', 'employer' => '0.00', 'items' => [], 'inputs' => ['employed_days' => $employedDays], 'exceptions' => ['no_structure']];
        }

        [$lop, $unprocessed] = $this->unpaidDays($employee, $from, $to);
        $unprocessed > 0 && $exceptions[] = 'attendance_unprocessed';
        $paidDays = max(0, $employedDays - $lop);
        $factor = bcdiv((string) $paidDays, (string) $daysInMonth, 8);

        $basic = '0.00';
        foreach ($structure->lines as $line) {
            $line->component->is_basic && $basic = bcadd($basic, (string) $line->amount, 2);
        }

        // earnings, then the ones that depend on gross
        $earned = [];
        foreach ($structure->lines->where('component.kind', 'earning') as $line) {
            $c = $line->component;
            $full = match ($c->method) {
                'fixed' => (string) $line->amount,
                'percent_basic' => $this->pct($basic, (string) $line->percent),
                default => null,
            };
            if ($full !== null) {
                $earned[] = [$c, $this->round($c->prorate ? bcmul($full, $factor, 6) : $full)];
            }
        }
        $baseGross = array_reduce($earned, fn ($c, $e) => bcadd($c, $e[1], 2), '0.00');
        foreach ($structure->lines->where('component.kind', 'earning')->where('component.method', 'percent_gross') as $line) {
            $earned[] = [$line->component, $this->pct($baseGross, (string) $line->percent)];
        }
        $taxable = '0.00';
        $gross = '0.00';
        foreach ($earned as [$c, $amount]) {
            $items[] = ['code' => $c->code, 'name' => $c->name, 'kind' => 'earning', 'amount' => $amount, 'note' => null];
            $gross = bcadd($gross, $amount, 2);
            $c->taxable && $taxable = bcadd($taxable, $amount, 2);
        }

        // overtime
        $otMinutes = $this->overtime->payableMinutes($employee, $start, $end);
        $hourly = bcdiv($basic, (string) $this->policy->nested('overtime', 'hourly_divisor', 208), 6);
        $multipliers = (array) $this->policy->nested('overtime', 'multipliers', []);
        $otPay = '0.00';
        foreach ($otMinutes as $dayType => $minutes) {
            $otPay = bcadd($otPay, $this->round(bcmul(bcmul(bcdiv((string) $minutes, '60', 6), $hourly, 6), (string) ($multipliers[$dayType] ?? 2.0), 6)), 2);
        }
        if (bccomp($otPay, '0', 2) > 0) {
            $items[] = ['code' => 'OT', 'name' => 'Overtime', 'kind' => 'earning', 'amount' => $otPay, 'note' => array_sum($otMinutes).' minutes approved'];
            $gross = bcadd($gross, $otPay, 2);
            $taxable = bcadd($taxable, $otPay, 2);
        }

        // adjustments waiting for this month
        foreach ($adjustments as $adj) {
            if ($adj->kind === 'earning') {
                $items[] = ['code' => 'ADJ+', 'name' => $adj->name, 'kind' => 'earning', 'amount' => (string) $adj->amount, 'note' => $adj->reason];
                $gross = bcadd($gross, (string) $adj->amount, 2);
                $taxable = bcadd($taxable, (string) $adj->amount, 2);
            }
        }

        // deductions
        $deductions = '0.00';
        foreach ($structure->lines->where('component.kind', 'deduction') as $line) {
            $c = $line->component;
            $amount = $c->method === 'fixed' ? (string) $line->amount : $this->pct($c->method === 'percent_gross' ? $gross : $basic, (string) $line->percent);
            $amount = $this->round($c->prorate ? bcmul($amount, $factor, 6) : $amount);
            $items[] = ['code' => $c->code, 'name' => $c->name, 'kind' => 'deduction', 'amount' => $amount, 'note' => null];
            $deductions = bcadd($deductions, $amount, 2);
        }
        $loanIds = [];
        foreach (HrLoan::query()->where(['employee_id' => $employee->id, 'status' => 'active'])->whereDate('start_month', '<=', $start->toDateString())->orderBy('id')->get() as $loan) {
            $take = bccomp((string) $loan->monthly_deduction, (string) $loan->balance, 2) < 0 ? (string) $loan->monthly_deduction : (string) $loan->balance;
            if (bccomp($take, '0', 2) > 0) {
                $items[] = ['code' => 'LOAN', 'name' => ucfirst($loan->kind).' repayment', 'kind' => 'deduction', 'amount' => $take, 'note' => $loan->public_id];
                $deductions = bcadd($deductions, $take, 2);
                $loanIds[] = $loan->id;
            }
        }
        foreach ($adjustments as $adj) {
            if ($adj->kind === 'deduction') {
                $items[] = ['code' => 'ADJ-', 'name' => $adj->name, 'kind' => 'deduction', 'amount' => (string) $adj->amount, 'note' => $adj->reason];
                $deductions = bcadd($deductions, (string) $adj->amount, 2);
            }
        }

        // employer contributions
        $employer = '0.00';
        foreach ($structure->lines->where('component.kind', 'employer') as $line) {
            $c = $line->component;
            $amount = $this->round($c->method === 'fixed' ? (string) $line->amount : $this->pct($basic, (string) $line->percent));
            $items[] = ['code' => $c->code, 'name' => $c->name, 'kind' => 'employer', 'amount' => $amount, 'note' => null];
            $employer = bcadd($employer, $amount, 2);
        }

        // tax: monthly share of the annual tax on this month's taxable earnings, annualized
        $slabs = $this->compensation->slabsOn($employee->organization_id, $end);
        $tax = $this->monthlyTax($taxable, $slabs);
        if (bccomp($tax, '0', 2) > 0) {
            $items[] = ['code' => 'TAX', 'name' => 'Income tax (TDS)', 'kind' => 'tax', 'amount' => $tax, 'note' => null];
        }

        $net = bcsub(bcsub($gross, $deductions, 2), $tax, 2);
        bccomp($net, '0', 2) < 0 && $exceptions[] = 'negative_net';
        if (! $employee->bank_account && ! $employee->mobile_wallet) {
            $exceptions[] = 'no_payment_account';
        }

        return [
            'gross' => $gross, 'deductions' => $deductions, 'tax' => $tax, 'net' => $net, 'employer' => $employer, 'items' => $items, 'exceptions' => array_values(array_unique($exceptions)),
            'inputs' => ['days_in_month' => $daysInMonth, 'employed_days' => $employedDays, 'unpaid_days' => $lop, 'paid_days' => $paidDays, 'structure_version' => $structure->version, 'overtime_minutes' => $otMinutes, 'loans' => $loanIds, 'taxable' => $taxable, 'basic' => $basic],
        ];
    }

    /** A supplementary run pays only the corrections entered for the month. */
    public function supplementary(HrEmployee $employee, Collection $adjustments): ?array
    {
        if ($adjustments->isEmpty()) {
            return null;
        }
        $items = [];
        $gross = $deductions = '0.00';
        foreach ($adjustments as $adj) {
            $earn = $adj->kind === 'earning';
            $items[] = ['code' => $earn ? 'ADJ+' : 'ADJ-', 'name' => $adj->name, 'kind' => $adj->kind, 'amount' => (string) $adj->amount, 'note' => $adj->reason];
            $earn ? $gross = bcadd($gross, (string) $adj->amount, 2) : $deductions = bcadd($deductions, (string) $adj->amount, 2);
        }
        $net = bcsub($gross, $deductions, 2);

        return ['gross' => $gross, 'deductions' => $deductions, 'tax' => '0.00', 'net' => $net, 'employer' => '0.00', 'items' => $items, 'inputs' => ['adjustments' => $adjustments->pluck('id')->all()],
            'exceptions' => array_values(array_filter([bccomp($net, '0', 2) < 0 ? 'negative_net' : null, ! $employee->bank_account && ! $employee->mobile_wallet ? 'no_payment_account' : null]))];
    }

    /** @return array{0: float, 1: int} unpaid days, and days that have no attendance record yet (attendance was not processed) */
    private function unpaidDays(HrEmployee $employee, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $records = HrAttendanceDay::query()->where('employee_id', $employee->id)->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->get()->keyBy(fn ($r) => $r->work_date->toDateString());
        $absent = 0.0;
        foreach ($records as $r) {
            $absent += match ($r->status) {
                'absent' => 1.0,
                'half_day' => 0.5,
                default => 0.0,
            };
        }
        // unpaid leave days that attendance shows as leave
        $unpaid = 0.0;
        HrLeaveRequest::query()->with('type')->where('employee_id', $employee->id)->where('status', 'approved')->whereDate('from_date', '<=', $to->toDateString())->whereDate('to_date', '>=', $from->toDateString())->each(function ($leave) use (&$unpaid, $from, $to) {
            if ($leave->type && ! $leave->type->is_paid) {
                $unpaid += $leave->half_day ? 0.5 : $this->daysWithin($leave, $from, $to);
            }
        });
        $missing = 0;
        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            if (! $records->has($d->toDateString())) {
                $missing++;
            }
        }

        return [$absent + $unpaid, $missing];
    }

    private function daysWithin(HrLeaveRequest $leave, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $s = max($from->toDateString(), $leave->from_date->toDateString());
        $e = min($to->toDateString(), $leave->to_date->toDateString());
        $count = 0;
        for ($d = CarbonImmutable::parse($s); $d->lte(CarbonImmutable::parse($e)); $d = $d->addDay()) {
            $count++;
        }

        return (float) $count;
    }

    /** @param  list<array{0: string, 1: ?string, 2: string}>  $slabs */
    public function monthlyTax(string $taxableMonthly, array $slabs): string
    {
        $annual = bcmul($taxableMonthly, '12', 2);
        $tax = '0';
        foreach ($slabs as [$from, $to, $rate]) {
            if (bccomp($annual, $from, 2) <= 0) {
                break;
            }
            $top = $to === null || bccomp($annual, $to, 2) < 0 ? $annual : $to;
            $tax = bcadd($tax, bcdiv(bcmul(bcsub($top, $from, 2), $rate, 6), '100', 6), 6);
        }
        $monthly = $this->round(bcdiv($tax, '12', 6));
        $min = (string) $this->policy->nested('payroll', 'minimum_monthly_tax', 0);

        return bccomp($tax, '0', 2) > 0 && bccomp($monthly, $min, 2) < 0 ? $this->round($min) : $monthly;
    }

    private function pct(string $of, string $percent): string
    {
        return $this->round(bcdiv(bcmul($of, $percent, 6), '100', 6));
    }

    /** Half-up to two places for any sign. */
    public function round(string $value): string
    {
        $neg = str_starts_with($value, '-');
        $abs = ltrim($value, '-');
        $r = bcadd($abs, '0.005', 2);

        return ($neg && bccomp($r, '0', 2) > 0 ? '-' : '').$r;
    }
}
