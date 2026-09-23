<?php

namespace App\Services\Appointments;

use App\Models\AppointmentSlot;
use App\Models\DoctorSchedule;
use App\Models\HospitalHoliday;
use App\Models\ProviderUnavailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates persisted AppointmentSlot rows for a DoctorSchedule (a recurring weekly "clinic
 * session" template) across a date range — the spec explicitly wants dated sessions/slots
 * persisted ahead of time rather than calculated live on every page load, "This makes booking and
 * concurrency management safer."
 */
class ScheduleGenerationService
{
    public function generateForRange(DoctorSchedule $schedule, Carbon $from, Carbon $to): Collection
    {
        $created = collect();
        $date = $from->copy();

        while ($date->lte($to)) {
            $created = $created->merge($this->generateForDate($schedule, $date->copy()));
            $date->addDay();
        }

        return $created;
    }

    public function generateForDate(DoctorSchedule $schedule, Carbon $date): Collection
    {
        if (! $schedule->is_active) {
            return collect();
        }

        if (strtolower($date->format('l')) !== $schedule->day_of_week) {
            return collect();
        }

        if (! $schedule->isEffectiveOn($date)) {
            return collect();
        }

        if ($this->isHoliday($schedule, $date)) {
            return collect();
        }

        // Already generated for this date — never create duplicate sessions.
        if (AppointmentSlot::where('schedule_id', $schedule->id)->whereDate('slot_datetime', $date)->exists()) {
            return collect();
        }

        return DB::transaction(function () use ($schedule, $date) {
            $slots = collect();
            $current = $date->copy()->setTimeFromTimeString($schedule->start_time->format('H:i'));
            $end = $date->copy()->setTimeFromTimeString($schedule->end_time->format('H:i'));
            $breakStart = $schedule->break_start_time ? $date->copy()->setTimeFromTimeString($schedule->break_start_time->format('H:i')) : null;
            $breakEnd = $schedule->break_end_time ? $date->copy()->setTimeFromTimeString($schedule->break_end_time->format('H:i')) : null;

            while ($current->lt($end)) {
                $withinBreak = $breakStart && $breakEnd && $current->gte($breakStart) && $current->lt($breakEnd);

                if (! $withinBreak && ! $this->isProviderUnavailable($schedule, $current, $schedule->slot_duration_minutes)) {
                    $slots->push(AppointmentSlot::create([
                        'company_id' => $schedule->company_id,
                        'branch_id' => $schedule->branch_id,
                        'doctor_id' => $schedule->doctor_id,
                        'schedule_id' => $schedule->id,
                        'slot_datetime' => $current->copy(),
                        'duration_minutes' => $schedule->slot_duration_minutes,
                        'max_capacity' => $schedule->default_capacity_per_slot,
                        'status' => 'available',
                    ]));
                }

                $current->addMinutes($schedule->slot_duration_minutes + $schedule->buffer_minutes);
            }

            return $slots;
        });
    }

    private function isHoliday(DoctorSchedule $schedule, Carbon $date): bool
    {
        return HospitalHoliday::where('company_id', $schedule->company_id)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $schedule->branch_id))
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $schedule->department_id))
            ->get()
            ->contains(fn (HospitalHoliday $holiday) => $holiday->appliesTo($date));
    }

    private function isProviderUnavailable(DoctorSchedule $schedule, Carbon $slotStart, int $durationMinutes): bool
    {
        if (! $schedule->provider_id) {
            return false;
        }

        $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

        return ProviderUnavailability::where('provider_id', $schedule->provider_id)
            ->where('start_at', '<', $slotEnd)
            ->where('end_at', '>', $slotStart)
            ->exists();
    }
}
