<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrLeaveRequest;
use App\Models\Hr\HrRosterEntry;
use App\Models\Hr\HrShift;
use App\Models\Hr\HrStaffingRequirement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Shifts and the duty roster (S04). A person is only put on a shift when nothing in the way: rest, weekly hours, leave, licence, days in a row. */
class RosterService
{
    public function __construct(private readonly HrPolicy $policy, private readonly CredentialService $credentials, private readonly WorkCalendar $calendar) {}

    public function createShift(int $organizationId, string $code, string $name, string $start, string $end, int $breakMinutes = 0, int $graceMinutes = 10): HrShift
    {
        if (HrShift::query()->where(['organization_id' => $organizationId, 'code' => strtoupper($code)])->exists()) {
            throw ValidationException::withMessages(['code' => 'That shift code is already used.']);
        }
        if ($start === $end) {
            throw ValidationException::withMessages(['end_time' => 'A shift has to end at a different time than it starts.']);
        }

        return HrShift::create(['organization_id' => $organizationId, 'code' => strtoupper($code), 'name' => $name, 'start_time' => $start, 'end_time' => $end, 'break_minutes' => $breakMinutes, 'grace_minutes' => $graceMinutes, 'is_night' => $end < $start]);
    }

    /** Start and end of the shift on a date; a night shift ends the next day. @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function window(HrShift $shift, CarbonInterface $on): array
    {
        $day = CarbonImmutable::parse($on->toDateString());
        $start = $day->setTimeFromTimeString((string) $shift->start_time);
        $end = $day->setTimeFromTimeString((string) $shift->end_time);

        return [$start, $end->lte($start) ? $end->addDay() : $end];
    }

    public function paidMinutes(HrShift $shift): int
    {
        [$s, $e] = $this->window($shift, CarbonImmutable::parse('2000-01-03'));

        return max(0, (int) $s->diffInMinutes($e) - $shift->break_minutes);
    }

    /** What stands in the way of putting this person on this shift that day. @return list<string> */
    public function conflicts(HrEmployee $employee, CarbonInterface $on, HrShift $shift): array
    {
        $why = [];
        if (! in_array($employee->status, (array) $this->policy->get('working_statuses'), true)) {
            $why[] = "The person is {$employee->status}.";
        }
        if (! $this->calendar->isEmployed($employee, $on)) {
            $why[] = 'The person is not employed on that day.';
        }
        if (HrLeaveRequest::query()->where('employee_id', $employee->id)->where('status', 'approved')->whereDate('from_date', '<=', $on->toDateString())->whereDate('to_date', '>=', $on->toDateString())->where('half_day', false)->exists()) {
            $why[] = 'The person is on approved leave.';
        }
        if (! $this->credentials->canBeRostered($employee, $on)) {
            $why[] = 'A required licence is missing or has expired ('.implode(', ', $this->credentials->gaps($employee, $on)).').';
        }

        [$start, $end] = $this->window($shift, $on);
        $minRest = (int) $this->policy->nested('roster', 'min_rest_hours', 11);
        $neighbours = HrRosterEntry::query()->with('shift')->where('employee_id', $employee->id)->whereDate('work_date', '>=', $on->copy()->subDay()->toDateString())->whereDate('work_date', '<=', $on->copy()->addDay()->toDateString())->get();
        foreach ($neighbours as $entry) {
            if ($entry->shift_id === $shift->id && $entry->work_date->toDateString() === $on->toDateString()) {
                $why[] = 'Already on that shift that day.';

                continue;
            }
            [$os, $oe] = $this->window($entry->shift, $entry->work_date);
            if ($os->lt($end) && $oe->gt($start)) {
                $why[] = "Overlaps {$entry->shift->name} on {$entry->work_date->toDateString()}.";
            } elseif ($oe->lte($start) && $oe->diffInMinutes($start) < $minRest * 60) {
                $why[] = "Less than $minRest hours of rest after {$entry->shift->name} on {$entry->work_date->toDateString()}.";
            } elseif ($os->gte($end) && $end->diffInMinutes($os) < $minRest * 60) {
                $why[] = "Less than $minRest hours of rest before {$entry->shift->name} on {$entry->work_date->toDateString()}.";
            }
        }

        $weekStart = CarbonImmutable::parse($on->toDateString())->startOfWeek();
        $week = HrRosterEntry::query()->with('shift')->where('employee_id', $employee->id)->whereDate('work_date', '>=', $weekStart->toDateString())->whereDate('work_date', '<=', $weekStart->addDays(6)->toDateString())->get();
        $minutes = $week->sum(fn ($e) => $this->paidMinutes($e->shift)) + $this->paidMinutes($shift);
        $cap = (int) $this->policy->nested('roster', 'max_weekly_hours', 60);
        if ($minutes > $cap * 60) {
            $why[] = 'That would take the week to '.round($minutes / 60, 1)." hours (limit $cap).";
        }

        $maxRun = (int) $this->policy->nested('roster', 'max_consecutive_days', 7);
        $dates = HrRosterEntry::query()->where('employee_id', $employee->id)->whereDate('work_date', '>=', $on->copy()->subDays($maxRun)->toDateString())->whereDate('work_date', '<=', $on->copy()->addDays($maxRun)->toDateString())->pluck('work_date')->map(fn ($d) => $d->toDateString())->push($on->toDateString())->unique()->sort()->values();
        $run = 1;
        for ($d = CarbonImmutable::parse($on->toDateString())->subDay(); $dates->contains($d->toDateString()); $d = $d->subDay()) {
            $run++;
        }
        for ($d = CarbonImmutable::parse($on->toDateString())->addDay(); $dates->contains($d->toDateString()); $d = $d->addDay()) {
            $run++;
        }
        if ($run > $maxRun) {
            $why[] = "That would be $run days in a row (limit $maxRun).";
        }

        return array_values(array_unique($why));
    }

    public function assign(HrEmployee $employee, CarbonInterface $on, HrShift $shift, User $by, ?string $note = null): HrRosterEntry
    {
        if ($shift->organization_id !== $employee->organization_id) {
            throw ValidationException::withMessages(['shift' => 'That shift belongs to another organization.']);
        }
        $why = $this->conflicts($employee, $on, $shift);
        if ($why !== []) {
            throw ValidationException::withMessages(['roster' => implode(' ', $why)]);
        }

        return HrRosterEntry::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'work_date' => $on->toDateString(), 'shift_id' => $shift->id, 'department_id' => $employee->department_id, 'note' => $note, 'created_by' => $by->id]);
    }

    public function remove(HrRosterEntry $entry): void
    {
        if ($entry->status === 'published' && $entry->work_date->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['roster' => 'A past, published duty cannot be removed; correct the attendance instead.']);
        }
        $entry->delete();
    }

    /** Publishes drafts in a range (optionally one department). @return int entries published */
    public function publish(int $organizationId, CarbonInterface $from, CarbonInterface $to, ?int $departmentId = null): int
    {
        return HrRosterEntry::query()->where('organization_id', $organizationId)->where('status', 'draft')->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))->update(['status' => 'published']);
    }

    public function require(int $organizationId, int $departmentId, HrShift $shift, int $minCount, ?int $jobId = null): HrStaffingRequirement
    {
        return HrStaffingRequirement::create(['organization_id' => $organizationId, 'department_id' => $departmentId, 'shift_id' => $shift->id, 'job_id' => $jobId, 'min_count' => $minCount]);
    }

    /** Shortfalls against the staffing needs for the days given. @return list<array{date: string, department_id: int, shift: string, needed: int, rostered: int}> */
    public function shortfalls(int $organizationId, CarbonInterface $from, CarbonInterface $to): array
    {
        $needs = HrStaffingRequirement::query()->with('shift')->where('organization_id', $organizationId)->get();
        $entries = HrRosterEntry::query()->with('employee:id,job_id')->where('organization_id', $organizationId)->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())->get()->groupBy(fn ($e) => $e->work_date->toDateString().'|'.$e->department_id.'|'.$e->shift_id);
        $out = [];
        for ($d = CarbonImmutable::parse($from->toDateString()); $d->lte($to); $d = $d->addDay()) {
            foreach ($needs as $need) {
                $rostered = ($entries[$d->toDateString().'|'.$need->department_id.'|'.$need->shift_id] ?? collect())->filter(fn ($e) => ! $need->job_id || $e->employee?->job_id === $need->job_id)->count();
                if ($rostered < $need->min_count) {
                    $out[] = ['date' => $d->toDateString(), 'department_id' => $need->department_id, 'shift' => $need->shift->name, 'needed' => $need->min_count, 'rostered' => $rostered];
                }
            }
        }

        return $out;
    }

    /** Swaps two people's duties on a day (both must stay conflict-free). */
    public function swap(HrRosterEntry $a, HrRosterEntry $b, User $by): void
    {
        DB::transaction(function () use ($a, $b, $by) {
            if ($a->work_date->toDateString() !== $b->work_date->toDateString()) {
                throw ValidationException::withMessages(['roster' => 'Swap duties on the same day.']);
            }
            [$ea, $eb, $sa, $sb, $day] = [$a->employee, $b->employee, $a->shift, $b->shift, $a->work_date];
            $a->delete();
            $b->delete();
            $this->assign($ea, $day, $sb, $by, 'Swap');
            $this->assign($eb, $day, $sa, $by, 'Swap');
        });
    }
}
