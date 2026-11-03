<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrOvertimeEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/** Overtime (S07): claimed from attendance, approved by someone else within daily and monthly caps, paid by payroll at the multiplier for the kind of day. */
class OvertimeService
{
    public function __construct(private readonly HrPolicy $policy, private readonly WorkCalendar $calendar) {}

    /** Creates pending entries from attendance days that show time after the shift. Safe to run again; decided entries are left alone. @return int created */
    public function syncFromAttendance(int $organizationId, CarbonInterface $from, CarbonInterface $to): int
    {
        $created = 0;
        HrAttendanceDay::query()->where('organization_id', $organizationId)->where('overtime_minutes', '>', 0)->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->with('employee')->orderBy('id')->each(function (HrAttendanceDay $day) use (&$created) {
            $entry = HrOvertimeEntry::query()->firstOrNew(['employee_id' => $day->employee_id, 'work_date' => $day->work_date->toDateString()]);
            if ($entry->exists && $entry->status !== 'pending') {
                return;
            }
            $entry->fill(['organization_id' => $day->organization_id, 'minutes_claimed' => $day->overtime_minutes, 'day_type' => $this->calendar->dayType($day->employee, $day->work_date)])->save();
            $entry->wasRecentlyCreated && $created++;
        });

        return $created;
    }

    /** Approves up to the claimed minutes, never more than the daily cap and never past the monthly cap. */
    public function approve(HrOvertimeEntry $entry, User $by, ?int $minutes = null): HrOvertimeEntry
    {
        $employee = HrEmployee::findOrFail($entry->employee_id);
        if ($employee->user_id === $by->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own overtime.']);
        }
        if ($entry->status !== 'pending') {
            throw ValidationException::withMessages(['status' => "This overtime is already {$entry->status}."]);
        }
        $minutes = min($minutes ?? $entry->minutes_claimed, $entry->minutes_claimed);
        if ($minutes <= 0) {
            throw ValidationException::withMessages(['minutes' => 'Approve at least a minute, or reject it.']);
        }
        $daily = (int) $this->policy->nested('overtime', 'daily_cap_minutes', 240);
        $monthly = (int) $this->policy->nested('overtime', 'monthly_cap_minutes', 3600);
        $minutes = min($minutes, $daily);
        $used = (int) HrOvertimeEntry::query()->where('employee_id', $employee->id)->whereIn('status', ['approved', 'paid'])->whereYear('work_date', $entry->work_date->year)->whereMonth('work_date', $entry->work_date->month)->sum('minutes_approved');
        $minutes = min($minutes, max(0, $monthly - $used));
        if ($minutes <= 0) {
            throw ValidationException::withMessages(['minutes' => 'The monthly overtime limit is already used up.']);
        }
        $entry->update(['status' => 'approved', 'minutes_approved' => $minutes, 'decided_by' => $by->id, 'decided_at' => now()]);

        return $entry->refresh();
    }

    public function reject(HrOvertimeEntry $entry, User $by): HrOvertimeEntry
    {
        if ($entry->status !== 'pending') {
            throw ValidationException::withMessages(['status' => "This overtime is already {$entry->status}."]);
        }
        $entry->update(['status' => 'rejected', 'decided_by' => $by->id, 'decided_at' => now()]);

        return $entry->refresh();
    }

    /** Approved, unpaid minutes in the month by kind of day. @return array<string, int> */
    public function payableMinutes(HrEmployee $employee, CarbonInterface $monthStart, CarbonInterface $monthEnd): array
    {
        return HrOvertimeEntry::query()->where(['employee_id' => $employee->id, 'status' => 'approved'])->whereDate('work_date', '>=', $monthStart->toDateString())->whereDate('work_date', '<=', $monthEnd->toDateString())
            ->selectRaw('day_type, SUM(minutes_approved) as m')->groupBy('day_type')->pluck('m', 'day_type')->map(fn ($m) => (int) $m)->all();
    }
}
