<?php

namespace App\Jobs\Appointments;

use App\Events\Appointments\AppointmentReminderDue;
use App\Mail\SystemNotification;
use App\Models\AppointmentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Delivery is intentionally shallow here: this module owns *scheduling* reminders, not building
 * SMS/WhatsApp/push gateway integrations (spec §37: "Do not implement provider-specific
 * integrations inside the scheduling domain"). Email is sent directly since the app already has
 * generic mail infrastructure; every channel also dispatches AppointmentReminderDue so a future
 * SMS/WhatsApp/push integration can subscribe without this job needing to change.
 */
class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public AppointmentReminder $reminder) {}

    public function handle(): void
    {
        if ($this->reminder->status !== 'pending') {
            return;
        }

        $appointment = $this->reminder->appointment;
        $patient = $appointment?->patient;

        if (! $appointment || ! $patient) {
            $this->reminder->update(['status' => 'failed', 'failure_reason' => 'Appointment or patient no longer exists.']);

            return;
        }

        try {
            if ($this->reminder->channel === 'email') {
                if (! $patient->email) {
                    throw new \RuntimeException('Patient has no email on file.');
                }

                Mail::to($patient->email)->queue(new SystemNotification(
                    'Appointment Reminder',
                    "Reminder: {$patient->full_name} has an appointment on {$appointment->appointment_date->format('M d, Y')} at {$appointment->appointment_time->format('H:i')}.",
                    ['appointment_id' => $appointment->id],
                ));
            }

            AppointmentReminderDue::dispatch($this->reminder);

            $this->reminder->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $this->reminder->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
        }
    }
}
