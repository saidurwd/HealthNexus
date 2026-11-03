<?php

namespace App\Listeners\Ot;

use App\Events\Ot\PatientReceivedForSurgery;
use App\Events\Ot\SurgicalComplicationRecorded;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnSurgeryTeamEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handlePatientReceived(PatientReceivedForSurgery $event): void
    {
        $surgery = $event->surgery;

        $recipients = $surgery->currentTeam->map(fn ($t) => $t->provider?->user)->filter()->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ot_surgery_reminder', [
            'patient' => $surgery->patient?->full_name ?? (string) $surgery->patient_id,
            'surgery_number' => $surgery->surgery_number,
            'scheduled_start_at' => 'now — patient received',
        ]);
    }

    public function handleComplication(SurgicalComplicationRecorded $event): void
    {
        $surgery = $event->complication->surgery;

        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ot_coordinator', 'ot_manager', 'hospital_admin']))
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $surgery->organization_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ot_missing_preoperative_requirement', [
            'patient' => $surgery->patient?->full_name ?? (string) $surgery->patient_id,
            'surgery_number' => $surgery->surgery_number,
            'missing' => 'complication reported: '.$event->complication->complication,
        ]);
    }
}
