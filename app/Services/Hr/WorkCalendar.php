<?php

namespace App\Services\Hr;

use App\Models\HospitalHoliday;
use App\Models\Hr\HrEmployee;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Which days count as working days for a person: employed, not their weekly off, not a holiday that applies to their branch and department. */
class WorkCalendar
{
    private const DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    /** @var array<string, Collection<int, HospitalHoliday>> */
    private array $holidays = [];

    public function isEmployed(HrEmployee $employee, CarbonInterface $on): bool
    {
        if (! $employee->joined_on || $on->toDateString() < $employee->joined_on->toDateString()) {
            return false;
        }

        return ! $employee->separated_on || $on->toDateString() <= $employee->separated_on->toDateString();
    }

    public function isWeeklyOff(HrEmployee $employee, CarbonInterface $on): bool
    {
        return self::DAYS[$on->dayOfWeek] === $employee->weekly_off;
    }

    public function holiday(HrEmployee $employee, CarbonInterface $on): ?HospitalHoliday
    {
        $year = $on->year;
        $this->holidays[$employee->organization_id.':'.$year] ??= HospitalHoliday::query()->where('organization_id', $employee->organization_id)
            ->where(fn ($q) => $q->whereYear('date', $year)->orWhere('is_recurring_annually', true))->get();

        return $this->holidays[$employee->organization_id.':'.$year]->first(function (HospitalHoliday $h) use ($employee, $on) {
            $same = $h->date->format('m-d') === $on->format('m-d') && ($h->date->year === $on->year || $h->is_recurring_annually);

            return $same && (! $h->branch_id || $h->branch_id === $employee->branch_id) && (! $h->department_id || $h->department_id === $employee->department_id);
        });
    }

    public function isWorkingDay(HrEmployee $employee, CarbonInterface $on): bool
    {
        return $this->isEmployed($employee, $on) && ! $this->isWeeklyOff($employee, $on) && $this->holiday($employee, $on) === null;
    }

    /** Working days from..to inclusive (half a day counts as 0.5 when the range is a single day). */
    public function workingDays(HrEmployee $employee, CarbonInterface $from, CarbonInterface $to): float
    {
        $days = 0;
        for ($d = CarbonImmutable::parse($from->toDateString()); $d->lte($to); $d = $d->addDay()) {
            $this->isWorkingDay($employee, $d) && $days++;
        }

        return (float) $days;
    }

    /** Day type for overtime: weekly_off, holiday or weekday. */
    public function dayType(HrEmployee $employee, CarbonInterface $on): string
    {
        return $this->holiday($employee, $on) ? 'holiday' : ($this->isWeeklyOff($employee, $on) ? 'weekly_off' : 'weekday');
    }

    public function forget(): void
    {
        $this->holidays = [];
    }
}
