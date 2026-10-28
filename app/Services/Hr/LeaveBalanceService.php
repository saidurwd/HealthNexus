<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveLedger;
use App\Models\Hr\HrLeaveType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/** Leave balances are the sum of an append-only ledger (S06). Accrual, carry forward and lapse are jobs that can be run again without doubling anything. */
class LeaveBalanceService
{
    public function __construct(private readonly HrPolicy $policy) {}

    /** Creates the standard leave types for an organization that has none. @return int created */
    public function seedTypes(int $organizationId): int
    {
        $count = 0;
        foreach ((array) $this->policy->get('leave_types') as $row) {
            $type = HrLeaveType::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $row['code']], $row);
            $type->wasRecentlyCreated && $count++;
        }

        return $count;
    }

    public function balance(HrEmployee $employee, HrLeaveType $type, ?CarbonInterface $asOf = null): string
    {
        return bcadd((string) HrLeaveLedger::query()->where(['employee_id' => $employee->id, 'leave_type_id' => $type->id])->when($asOf, fn ($q) => $q->whereDate('effective_on', '<=', $asOf->toDateString()))->sum('days'), '0', 2);
    }

    public function credit(HrEmployee $employee, HrLeaveType $type, string $days, string $entryType, CarbonInterface $on, string $key, ?string $note = null, ?User $by = null, ?string $refType = null, ?int $refId = null): HrLeaveLedger
    {
        $existing = HrLeaveLedger::query()->where(['organization_id' => $employee->organization_id, 'idempotency_key' => $key])->first();
        if ($existing) {
            return $existing;
        }

        return HrLeaveLedger::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'leave_type_id' => $type->id, 'entry_type' => $entryType, 'days' => $days, 'effective_on' => $on->toDateString(), 'ref_type' => $refType, 'ref_id' => $refId, 'note' => $note, 'idempotency_key' => $key, 'created_by' => $by?->id, 'created_at' => now()]);
    }

    /** A manual correction needs a reason and is its own ledger row. */
    public function adjust(HrEmployee $employee, HrLeaveType $type, string $days, string $reason, User $by): HrLeaveLedger
    {
        if (bccomp($days, '0', 2) === 0 || trim($reason) === '') {
            throw ValidationException::withMessages(['days' => 'Give a change and a reason.']);
        }

        return $this->credit($employee, $type, $days, 'adjustment', now(), 'ADJ:'.$employee->id.':'.$type->id.':'.now()->format('YmdHis').':'.bin2hex(random_bytes(3)), $reason, $by);
    }

    /** The year's entitlement of every upfront type, pro-rated by months left for a person who joined during the year. Safe to run again. @return int rows written */
    public function openYear(int $organizationId, int $year): int
    {
        $written = 0;
        $types = HrLeaveType::query()->where(['organization_id' => $organizationId, 'status' => 'active', 'accrual' => 'upfront'])->where('annual_days', '>', 0)->get();
        HrEmployee::query()->where('organization_id', $organizationId)->whereIn('status', (array) $this->policy->get('working_statuses'))->whereNotNull('joined_on')->orderBy('id')->each(function (HrEmployee $employee) use ($types, $year, &$written) {
            foreach ($types as $type) {
                if ($type->gender_restriction && $type->gender_restriction !== $employee->gender) {
                    continue;
                }
                $months = $employee->joined_on->year < $year ? 12 : ($employee->joined_on->year === $year ? 13 - $employee->joined_on->month : 0);
                if ($months <= 0) {
                    continue;
                }
                $days = $months === 12 ? (string) $type->annual_days : (string) (round((float) $type->annual_days * $months / 12 * 2) / 2);
                $row = $this->credit($employee, $type, $days, 'opening', now()->setDate($year, 1, 1), "OPEN:$year:{$employee->id}:{$type->id}", "Entitlement for $year");
                $row->wasRecentlyCreated && $written++;
            }
        });

        return $written;
    }

    /** One month of earned leave for every working person, for types that accrue monthly. @return int rows written */
    public function accrueMonth(int $organizationId, CarbonInterface $month): int
    {
        $written = 0;
        $key = $month->format('Ym');
        $types = HrLeaveType::query()->where(['organization_id' => $organizationId, 'status' => 'active', 'accrual' => 'monthly'])->where('annual_days', '>', 0)->get();
        HrEmployee::query()->where('organization_id', $organizationId)->whereIn('status', (array) $this->policy->get('working_statuses'))->whereNotNull('joined_on')->whereDate('joined_on', '<=', $month->copy()->endOfMonth()->toDateString())->orderBy('id')->each(function (HrEmployee $employee) use ($types, $month, $key, &$written) {
            foreach ($types as $type) {
                $row = $this->credit($employee, $type, bcdiv((string) $type->annual_days, '12', 2), 'accrual', $month->copy()->endOfMonth(), "ACCR:$key:{$employee->id}:{$type->id}", 'Monthly accrual');
                $row->wasRecentlyCreated && $written++;
            }
        });

        return $written;
    }

    /** Year end: carry forward up to the type's limit, lapse the rest. Safe to run again. @return array{carried: int, lapsed: int} */
    public function rollYear(int $organizationId, int $year): array
    {
        $carried = $lapsed = 0;
        $end = now()->setDate($year, 12, 31);
        $next = now()->setDate($year + 1, 1, 1);
        HrEmployee::query()->where('organization_id', $organizationId)->orderBy('id')->each(function (HrEmployee $employee) use ($organizationId, $year, $end, $next, &$carried, &$lapsed) {
            foreach (HrLeaveType::query()->where(['organization_id' => $organizationId, 'status' => 'active'])->where('annual_days', '>', 0)->get() as $type) {
                $balance = $this->balance($employee, $type, $end);
                if (bccomp($balance, '0', 2) <= 0) {
                    continue;
                }
                $carry = bccomp($balance, (string) $type->carry_forward_max, 2) < 0 ? $balance : (string) $type->carry_forward_max;
                $lapse = bcsub($balance, $carry, 2);
                if (bccomp($lapse, '0', 2) > 0) {
                    $this->credit($employee, $type, bcmul($lapse, '-1', 2), 'lapse', $end, "LAPSE:$year:{$employee->id}:{$type->id}", 'Lapsed at year end')->wasRecentlyCreated && $lapsed++;
                }
                if (bccomp($carry, '0', 2) > 0) {
                    // moves nothing by itself: the balance simply carries, the row records how much was allowed to
                    $this->credit($employee, $type, '0.00', 'carry_forward', $next, "CARRY:$year:{$employee->id}:{$type->id}", "Carried forward $carry day(s)")->wasRecentlyCreated && $carried++;
                }
            }
        });

        return ['carried' => $carried, 'lapsed' => $lapsed];
    }
}
