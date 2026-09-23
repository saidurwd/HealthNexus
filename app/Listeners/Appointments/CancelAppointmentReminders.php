<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentNoShow;
use App\Services\Appointments\AppointmentReminderService;

class CancelAppointmentReminders
{
    public function __construct(private AppointmentReminderService $reminders) {}

    public function handleCancelled(AppointmentCancelled $event): void
    {
        $this->reminders->cancelPendingRemindersFor($event->appointment);
    }

    public function handleNoShow(AppointmentNoShow $event): void
    {
        $this->reminders->cancelPendingRemindersFor($event->appointment);
    }
}
