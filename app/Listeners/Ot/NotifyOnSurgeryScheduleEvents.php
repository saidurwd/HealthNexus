<?php

namespace App\Listeners\Ot;

use App\Events\Ot\SurgeryCancelled;
use App\Events\Ot\SurgeryPostponed;
use App\Events\Ot\SurgeryScheduled;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnSurgeryScheduleEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleScheduled(SurgeryScheduled $event): void
    {
        $this->notify($event->surgery, 'ot_surgery_reminder', [
            'patient' => $event->surgery->patient?->full_name ?? (string) $event->surgery->patient_id,
            'surgery_number' => $event->surgery->surgery_number,
            'scheduled_start_at' => $event->surgery->scheduled_start_at?->toDateTimeString() ?? '—',
        ]);
    }

    public function handlePostponed(SurgeryPostponed $event): void
    {
        $this->notify($event->surgery, 'ot_missing_preoperative_requirement', [
            'patient' => $event->surgery->patient?->full_name ?? (string) $event->surgery->patient_id,
            'surgery_number' => $event->surgery->surgery_number,
            'missing' => 'postponed: '.($event->surgery->cancellation_reason ?? 'no reason given'),
        ]);
    }

    public function handleCancelled(SurgeryCancelled $event): void
    {
        $this->notify($event->surgery, 'ot_missing_preoperative_requirement', [
            'patient' => $event->surgery->patient?->full_name ?? (string) $event->surgery->patient_id,
            'surgery_number' => $event->surgery->surgery_number,
            'missing' => 'cancelled: '.($event->surgery->cancellation_reason ?? 'no reason given'),
        ]);
    }

    private function notify($surgery, string $template, array $params): void
    {
        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ot_coordinator', 'ot_manager']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $surgery->company_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, $template, $params);
    }
}
