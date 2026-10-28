<?php

namespace App\Services\Hr;

use App\Models\Hr\HrDependent;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLoan;
use App\Models\Hr\HrLoanEntry;
use App\Models\Hr\HrPayComponent;
use App\Models\Hr\HrPayrollPeriod;
use App\Models\Hr\HrSalaryStructure;
use App\Models\Hr\HrTaxSlab;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pay components, salary structures, loans, dependents and tax slabs (S08). A salary structure is a version that applies from a date; it is never edited, a change
 * is a new version. Salary is class C4: only the services here and payroll read it, and screens ask for the compensation permissions first.
 */
class CompensationService
{
    public function __construct(private readonly HrPolicy $policy) {}

    /** Creates the standard components for an organization that has none. @return int created */
    public function seedComponents(int $organizationId): int
    {
        $count = 0;
        foreach ((array) $this->policy->get('pay_components') as $row) {
            $component = HrPayComponent::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $row['code']], $row);
            $component->wasRecentlyCreated && $count++;
        }

        return $count;
    }

    /**
     * @param  list<array{component: string, amount?: float|int|string|null, percent?: float|int|string|null}>  $lines  component codes
     */
    public function newStructure(HrEmployee $employee, CarbonInterface $effectiveFrom, array $lines, User $by, ?string $reason = null): HrSalaryStructure
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'A salary structure needs components.']);
        }
        $closed = HrPayrollPeriod::query()->where('organization_id', $employee->organization_id)->where('status', 'closed')->orderByDesc('month_end')->first();
        if ($closed && $effectiveFrom->toDateString() <= $closed->month_end->toDateString()) {
            throw ValidationException::withMessages(['effective_from' => 'That month is already paid. Start the new pay from '.$closed->month_end->copy()->addDay()->toDateString().' or later, and pay the difference as an adjustment.']);
        }

        return DB::transaction(function () use ($employee, $effectiveFrom, $lines, $by, $reason) {
            $previous = HrSalaryStructure::query()->where('employee_id', $employee->id)->orderByDesc('version')->lockForUpdate()->first();
            if ($previous && $effectiveFrom->toDateString() <= $previous->effective_from->toDateString()) {
                throw ValidationException::withMessages(['effective_from' => 'The new structure must start after the current one ('.$previous->effective_from->toDateString().').']);
            }
            $resolved = [];
            $hasBasic = false;
            foreach ($lines as $line) {
                $component = HrPayComponent::query()->where(['organization_id' => $employee->organization_id, 'code' => $line['component'], 'status' => 'active'])->first();
                if (! $component) {
                    throw ValidationException::withMessages(['lines' => "No such pay component [{$line['component']}]."]);
                }
                if ($component->method === 'fixed' && (! isset($line['amount']) || bccomp((string) $line['amount'], '0', 2) < 0)) {
                    throw ValidationException::withMessages(['lines' => "{$component->name} needs an amount."]);
                }
                if ($component->method !== 'fixed' && (! isset($line['percent']) || bccomp((string) $line['percent'], '0', 4) < 0 || bccomp((string) $line['percent'], '100', 4) > 0)) {
                    throw ValidationException::withMessages(['lines' => "{$component->name} needs a percentage between 0 and 100."]);
                }
                if ($component->is_basic) {
                    $hasBasic = $hasBasic || bccomp((string) ($line['amount'] ?? 0), '0', 2) > 0;
                }
                $resolved[] = [$component, $line];
            }
            if (! $hasBasic) {
                throw ValidationException::withMessages(['lines' => 'The structure needs a basic pay above zero.']);
            }
            $previous?->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);
            $structure = HrSalaryStructure::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'version' => ($previous->version ?? 0) + 1, 'effective_from' => $effectiveFrom->toDateString(), 'reason' => $reason, 'created_by' => $by->id]);
            foreach ($resolved as [$component, $line]) {
                $structure->lines()->create(['component_id' => $component->id, 'amount' => $component->method === 'fixed' ? $line['amount'] : null, 'percent' => $component->method !== 'fixed' ? $line['percent'] : null]);
            }

            return $structure->refresh();
        });
    }

    public function structureOn(HrEmployee $employee, CarbonInterface $on): ?HrSalaryStructure
    {
        return HrSalaryStructure::query()->with('lines.component')->where('employee_id', $employee->id)->whereDate('effective_from', '<=', $on->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on->toDateString()))->orderByDesc('version')->first();
    }

    // ---- loans and advances ----

    public function requestLoan(HrEmployee $employee, string $kind, string $amount, string $monthlyDeduction, CarbonInterface $startMonth, ?string $reason = null): HrLoan
    {
        if (! in_array($kind, (array) $this->policy->get('loan_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => 'Use loan or advance.']);
        }
        if (bccomp($amount, '0', 2) <= 0 || bccomp($monthlyDeduction, '0', 2) <= 0 || bccomp($monthlyDeduction, $amount, 2) > 0) {
            throw ValidationException::withMessages(['amount' => 'The amount and the monthly deduction must be above zero, and the deduction cannot exceed the amount.']);
        }
        if (! in_array($employee->status, ['active', 'on_leave'], true)) {
            throw ValidationException::withMessages(['employee' => 'Only a working employee can borrow.']);
        }
        if (HrLoan::query()->where('employee_id', $employee->id)->where('kind', 'loan')->whereIn('status', ['requested', 'active'])->exists() && $kind === 'loan') {
            throw ValidationException::withMessages(['kind' => 'This person already has a loan that is not repaid.']);
        }

        return HrLoan::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'kind' => $kind, 'amount' => $amount, 'monthly_deduction' => $monthlyDeduction, 'balance' => 0, 'start_month' => $startMonth->copy()->startOfMonth()->toDateString(), 'reason' => $reason]);
    }

    public function approveLoan(HrLoan $loan, User $by): HrLoan
    {
        return DB::transaction(function () use ($loan, $by) {
            $loan = HrLoan::query()->lockForUpdate()->findOrFail($loan->id);
            if ($loan->status !== 'requested') {
                throw ValidationException::withMessages(['status' => "A {$loan->status} loan cannot be approved."]);
            }
            $employee = HrEmployee::findOrFail($loan->employee_id);
            if ($employee->user_id === $by->id) {
                throw ValidationException::withMessages(['approval' => 'You cannot approve your own loan.']);
            }
            $loan->update(['status' => 'active', 'balance' => $loan->amount, 'approved_by' => $by->id, 'approved_at' => now()]);
            HrLoanEntry::create(['loan_id' => $loan->id, 'amount' => $loan->amount, 'entry_type' => 'disbursement', 'created_at' => now()]);

            return $loan->refresh();
        });
    }

    public function addDependent(HrEmployee $employee, string $name, string $relation, ?string $dob = null): HrDependent
    {
        return HrDependent::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'name' => $name, 'relation' => $relation, 'date_of_birth' => $dob]);
    }

    // ---- tax slabs ----

    /** Slabs as effective-dated rows: [from, to|null, percent]. Replaces nothing — a new set applies from its date. */
    public function setSlabs(int $organizationId, CarbonInterface $effectiveFrom, array $slabs): void
    {
        $last = '0';
        foreach ($slabs as [$from, $to, $rate]) {
            if (bccomp((string) $from, $last, 2) !== 0 || ($to !== null && bccomp((string) $to, (string) $from, 2) <= 0) || $rate < 0 || $rate > 100) {
                throw ValidationException::withMessages(['slabs' => 'Slabs must run on from one to the next without gaps, with rates between 0 and 100.']);
            }
            $last = $to === null ? $last : (string) $to;
        }
        DB::transaction(function () use ($organizationId, $effectiveFrom, $slabs) {
            HrTaxSlab::query()->where('organization_id', $organizationId)->whereDate('effective_from', $effectiveFrom->toDateString())->delete();
            foreach ($slabs as [$from, $to, $rate]) {
                HrTaxSlab::create(['organization_id' => $organizationId, 'effective_from' => $effectiveFrom->toDateString(), 'from_amount' => $from, 'to_amount' => $to, 'rate_percent' => $rate]);
            }
        });
    }

    /** The slabs in force on a date: the latest set at or before it, else the example slabs from config. @return list<array{0: string, 1: ?string, 2: string}> */
    public function slabsOn(int $organizationId, CarbonInterface $on): array
    {
        $date = HrTaxSlab::query()->where('organization_id', $organizationId)->whereDate('effective_from', '<=', $on->toDateString())->max('effective_from');
        if ($date) {
            return HrTaxSlab::query()->where('organization_id', $organizationId)->whereDate('effective_from', $date)->orderBy('from_amount')->get()->map(fn ($s) => [(string) $s->from_amount, $s->to_amount === null ? null : (string) $s->to_amount, (string) $s->rate_percent])->all();
        }

        return array_map(fn ($s) => [(string) $s[0], $s[1] === null ? null : (string) $s[1], (string) $s[2]], (array) $this->policy->get('tax_slabs'));
    }
}
