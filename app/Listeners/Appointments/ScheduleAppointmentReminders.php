<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentCreated;
use App\Events\Appointments\AppointmentRescheduled;
use App\Services\Appointments\AppointmentReminderService;

class ScheduleAppointmentReminders
{
    public function __construct(private AppointmentReminderService $reminders) {}

    public function handleCreated(AppointmentCreated $event): void
    {
        $this->reminders->scheduleRemindersFor($event->appointment);
    }

    public function handleRescheduled(AppointmentRescheduled $event): void
    {
        $this->reminders->scheduleRemindersFor($event->appointment);
    }
}
