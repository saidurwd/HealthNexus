<?php

namespace App\Events\Appointments;

use App\Models\AppointmentReminder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderDue
{
    use Dispatchable, SerializesModels;

    public function __construct(public AppointmentReminder $reminder) {}
}
