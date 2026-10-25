<?php

namespace App\Listeners\Icu;

use App\Events\Icu\ICUPatientAdmitted;
use App\Events\Icu\ICUPatientTransferred;
use App\Models\Icu\IcuEpisode;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnIcuEpisodeEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleAdmitted(ICUPatientAdmitted $event): void
    {
        $this->notify($event->episode, 'icu_admission', [
            'patient' => $event->episode->patient?->full_name ?? (string) $event->episode->patient_id,
            'episode_number' => $event->episode->episode_number,
        ]);
    }

    public function handleTransferred(ICUPatientTransferred $event): void
    {
        $lastEvent = $event->episode->events()->latest()->first();

        $this->notify($event->episode, 'icu_pending_transfer', [
            'patient' => $event->episode->patient?->full_name ?? (string) $event->episode->patient_id,
            'episode_number' => $event->episode->episode_number,
            'destination' => $lastEvent?->destination ?? 'unspecified',
        ]);
    }

    private function notify(IcuEpisode $episode, string $template, array $params): void
    {
        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['icu_coordinator', 'icu_manager', 'icu_charge_nurse']))
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $episode->organization_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, $template, $params);
    }
}
