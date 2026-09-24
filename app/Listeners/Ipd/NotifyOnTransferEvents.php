<?php

namespace App\Listeners\Ipd;

use App\Events\Ipd\PatientTransferred;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnTransferEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleCompleted(PatientTransferred $event): void
    {
        $movement = $event->movement;
        $admission = $movement->admission;

        // whereHas against the roles pivot, not Spatie's role() scope — that throws
        // RoleDoesNotExist if a listed role isn't defined yet, which would crash this listener
        // during an ordinary transfer completion.
        $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ipd_coordinator', 'ward_manager']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $movement->company_id))
            ->get();

        if ($recipients->isEmpty() || ! $admission) {
            return;
        }

        $this->notifications->send($recipients, 'ipd_transfer_completed', [
            'patient' => $movement->patient?->full_name ?? (string) $movement->patient_id,
            'admission_number' => $admission->admission_number,
            'bed_code' => $movement->toBed?->bed_code ?? '—',
        ]);
    }
}
