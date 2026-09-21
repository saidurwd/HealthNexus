<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientRegisteredNotification extends BaseNotification
{
    use Queueable;

    public function __construct(public string $patientName, public string $patientNumber)
    {
    }

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'New Patient Registered',
            'message' => "Patient {$this->patientName} has been registered with MRN {$this->patientNumber}.",
            'type' => 'patient.registered',
            'action_url' => route('admin.patients.show', ['patient' => $this->patientNumber]),
        ];
    }
}
