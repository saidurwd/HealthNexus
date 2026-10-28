<?php

namespace App\Services\Hr;

use App\Models\Hr\HrAttendanceDay;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrPunch;
use App\Models\Hr\HrRegularization;
use App\Models\Hr\HrRosterEntry;
use App\Models\Hr\HrShift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * From raw punches, the roster, leave and holidays to one attendance record per person per day (S05). It can be run again at any time — a late-arriving punch,
 * an approved correction, a leave decision — and gives the same answer for the same inputs. A day locked by payroll is never touched.
 */
class AttendanceService
{
    public function __construct(private readonly HrPolicy $policy, private readonly RosterService $roster, private readonly WorkCalendar $calendar) {}

    /** Works out one day for one person and stores it. Returns null when the day is locked or the person was not employed. */
    public function calculateDay(HrEmployee $employee, CarbonInterface $on): ?HrAttendanceDay
    {
        $date = CarbonImmutable::parse($on->toDateString());
        $existing = HrAttendanceDay::query()->where(['employee_id' => $employee->id, 'work_date' => $date->toDateString()])->first();
        if ($existing?->locked) {
            return null;
        }
        if (! $this->calendar->isEmployed($employee, $date)) {
            $existing?->delete();

            return null;
        }

        $leave = HrLeaveRequest::query()->where('employee_id', $employee->id)->where('status', 'approved')->whereDate('from_date', '<=', $date->toDateString())->whereDate('to_date', '>=', $date->toDateString())->first();
        $entry = HrRosterEntry::query()->with('shift')->where(['employee_id' => $employee->id])->whereDate('work_date', $date->toDateString())->orderBy('id')->first();
        $shift = $entry ? $entry->shift : $this->defaultShift($employee);
        $holiday = $this->calendar->holiday($employee, $date);
        $weeklyOff = $this->calendar->isWeeklyOff($employee, $date);
        $regularization = HrRegularization::query()->where(['employee_id' => $employee->id, 'status' => 'approved'])->whereDate('work_date', $date->toDateString())->latest('id')->first();

        $punches = $this->punchesFor($employee, $date, $shift);
        $first = $last = null;
        if ($regularization && ($regularization->requested_in || $regularization->requested_out)) {
            $first = $regularization->requested_in ?? $punches->first();
            $last = $regularization->requested_out ?? $punches->last();
        } elseif ($punches->count() >= 1) {
            $first = $punches->first();
            $last = $punches->count() >= 2 ? $punches->last() : null;
        }

        $attrs = ['organization_id' => $employee->organization_id, 'shift_id' => $shift?->id, 'first_in' => $first, 'last_out' => $last, 'worked_minutes' => 0, 'late_minutes' => 0, 'early_out_minutes' => 0, 'overtime_minutes' => 0,
            'leave_request_id' => $leave?->id, 'regularization_id' => $regularization?->id];

        if ($leave && ! $leave->half_day) {
            $attrs['status'] = 'leave';
        } elseif ($shift && $first) {
            $attrs = array_merge($attrs, $this->measure($shift, $date, $first, $last, $leave !== null)); // a leave request still here is a half day: a full day was handled above
        } elseif ($holiday) {
            $attrs['status'] = 'holiday';
        } elseif ($weeklyOff && ! $entry) {
            $attrs['status'] = 'weekly_off';
        } elseif ($shift) {
            $attrs['status'] = $leave ? 'leave' : 'absent';
        } else {
            $attrs['status'] = 'unrostered';
        }

        return HrAttendanceDay::updateOrCreate(['employee_id' => $employee->id, 'work_date' => $date->toDateString()], $attrs);
    }

    /** @return array<string, mixed> */
    private function measure(HrShift $shift, CarbonImmutable $date, CarbonInterface $first, ?CarbonInterface $last, bool $halfDayLeave): array
    {
        [$start, $end] = $this->roster->window($shift, $date);
        $late = max(0, (int) $start->addMinutes($shift->grace_minutes)->diffInMinutes($first, false));
        if ($last === null) {
            // One touch only: the person came but the way out is unknown. It is a half day until someone corrects it.
            return ['status' => 'half_day', 'late_minutes' => $late];
        }
        $worked = max(0, (int) $first->diffInMinutes($last) - $shift->break_minutes);
        $early = max(0, (int) $last->diffInMinutes($end, false));
        $afterShift = (int) $end->diffInMinutes($last, false);
        $ot = $afterShift >= (int) $this->policy->nested('attendance', 'overtime_min_minutes', 30) ? $afterShift : 0;
        $paid = max(1, $this->roster->paidMinutes($shift));
        $status = ($worked < $paid * ((int) $this->policy->nested('attendance', 'half_day_below_percent', 50) / 100)) && ! $halfDayLeave ? 'half_day' : ($late > 0 ? 'late' : 'present');

        return ['status' => $status, 'worked_minutes' => min($worked, 65535), 'late_minutes' => min($late, 65535), 'early_out_minutes' => min($early, 65535), 'overtime_minutes' => min($ot, 65535)];
    }

    /** Punches of the person that belong to this day's shift window (or the calendar day when there is no shift), duplicates within a couple of minutes folded. */
    private function punchesFor(HrEmployee $employee, CarbonImmutable $date, ?HrShift $shift)
    {
        if ($shift) {
            [$start, $end] = $this->roster->window($shift, $date);
            $from = $start->subHours((int) $this->policy->nested('attendance', 'punch_window_before_hours', 3));
            $to = $end->addHours((int) $this->policy->nested('attendance', 'punch_window_after_hours', 6));
        } else {
            [$from, $to] = [$date->startOfDay(), $date->endOfDay()];
        }
        $gap = (int) $this->policy->nested('attendance', 'duplicate_punch_minutes', 2);
        $kept = collect();
        foreach (HrPunch::query()->where('employee_id', $employee->id)->where('punched_at', '>=', $from)->where('punched_at', '<=', $to)->orderBy('punched_at')->pluck('punched_at') as $at) {
            if ($kept->isEmpty() || $kept->last()->diffInMinutes($at) >= $gap) {
                $kept->push(CarbonImmutable::parse($at));
            }
        }

        return $kept;
    }

    private function defaultShift(HrEmployee $employee): ?HrShift
    {
        $code = $this->policy->nested('attendance', 'unrostered_default_shift');

        return $code ? HrShift::query()->where(['organization_id' => $employee->organization_id, 'code' => $code])->first() : null;
    }

    /** Recalculates a range for everyone working (or the given people). @return int days written */
    public function processRange(int $organizationId, CarbonInterface $from, CarbonInterface $to, ?array $employeeIds = null): int
    {
        $count = 0;
        HrEmployee::query()->where('organization_id', $organizationId)->when($employeeIds, fn ($q) => $q->whereIn('id', $employeeIds))->whereNotNull('joined_on')->orderBy('id')->each(function (HrEmployee $employee) use ($from, $to, &$count) {
            for ($d = CarbonImmutable::parse($from->toDateString()); $d->lte($to); $d = $d->addDay()) {
                $this->calculateDay($employee, $d) && $count++;
            }
        });

        return $count;
    }

    // ---- regularization: the person says "my punch is wrong / missing"; somebody else decides ----

    public function requestRegularization(HrEmployee $employee, CarbonInterface $on, ?CarbonInterface $in, ?CarbonInterface $out, string $reason): HrRegularization
    {
        if ($in === null && $out === null) {
            throw ValidationException::withMessages(['requested_in' => 'Say when you came in or went out.']);
        }
        if ($in && $out && $out->lte($in)) {
            throw ValidationException::withMessages(['requested_out' => 'The way out is before the way in.']);
        }
        if ($on->gt(now())) {
            throw ValidationException::withMessages(['work_date' => 'You cannot correct a day that has not happened.']);
        }
        if (HrAttendanceDay::query()->where(['employee_id' => $employee->id, 'locked' => true])->whereDate('work_date', $on->toDateString())->exists()) {
            throw ValidationException::withMessages(['work_date' => 'That month is closed for payroll; ask HR.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Say why.']);
        }

        return HrRegularization::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'work_date' => $on->toDateString(), 'requested_in' => $in, 'requested_out' => $out, 'reason' => $reason])->refresh();
    }

    public function decideRegularization(HrRegularization $regularization, User $by, bool $approve, ?string $note = null): HrRegularization
    {
        if ($regularization->status !== 'requested') {
            throw ValidationException::withMessages(['status' => "This request is already {$regularization->status}."]);
        }
        $employee = HrEmployee::findOrFail($regularization->employee_id);
        if ($employee->user_id === $by->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot decide your own correction.']);
        }
        $regularization->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'decision_note' => $note]);
        $approve && $this->calculateDay($employee, $regularization->work_date);

        return $regularization->refresh();
    }
}
