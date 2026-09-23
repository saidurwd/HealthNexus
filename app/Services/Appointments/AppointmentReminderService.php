<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\AppointmentReminderRule;
use Illuminate\Support\Collection;

/**
 * Reminder timing is entirely rule-driven (AppointmentReminderRule, configurable by hospital/
 * branch/department/provider/appointment type/channel/offset) — never hardcoded ("24 hours
 * before" per spec §41). Called from ScheduleAppointmentReminders on AppointmentCreated/
 * AppointmentRescheduled, not from the booking transaction itself, so a reminder-scheduling
 * failure can never block a booking.
 */
class AppointmentReminderService
{
    public function scheduleRemindersFor(Appointment $appointment): Collection
    {
        if (! $appointment->appointment_date || ! $appointment->appointment_time) {
            return collect();
        }

        $appointmentAt = $appointment->appointment_date->copy()->setTimeFromTimeString($appointment->appointment_time->format('H:i'));

        $rules = AppointmentReminderRule::where('company_id', $appointment->company_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (AppointmentReminderRule $rule) => $rule->appliesTo($appointment));

        return $rules->map(function (AppointmentReminderRule $rule) use ($appointment, $appointmentAt) {
            $scheduledFor = $appointmentAt->copy()->subMinutes($rule->offset_minutes);

            return AppointmentReminder::updateOrCreate(
                ['appointment_id' => $appointment->id, 'rule_id' => $rule->id],
                [
                    'company_id' => $appointment->company_id,
                    'channel' => $rule->channel,
                    'scheduled_for' => $scheduledFor,
                    'status' => $scheduledFor->isFuture() ? 'pending' : 'cancelled',
                ],
            );
        });
    }

    /**
     * Called when an appointment is cancelled/rescheduled away from its original time — pending
     * reminders tied to the stale time must not fire.
     */
    public function cancelPendingRemindersFor(Appointment $appointment): void
    {
        AppointmentReminder::where('appointment_id', $appointment->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    public function dueReminders(): Collection
    {
        return AppointmentReminder::where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->with('appointment.patient')
            ->get();
    }
}
