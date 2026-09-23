<?php

namespace App\Events\Appointments;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Appointment $appointment) {}
}
