<?php

namespace App\Listeners\Ipd;

use App\Events\Ipd\AdmissionApproved;
use App\Events\Ipd\BedAllocated;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnAdmissionEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleApproved(AdmissionApproved $event): void
    {
        $request = $event->request;
        $recipients = $this->coordinatorsFor($request->organization_id);

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ipd_admission_approved', [
            'patient' => $request->patient?->full_name ?? (string) $request->patient_id,
        ]);
    }

    public function handleBedAllocated(BedAllocated $event): void
    {
        $allocation = $event->allocation;
        $admission = $allocation->admission;
        $recipients = $this->coordinatorsFor($allocation->organization_id);

        if (! $admission || $recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ipd_bed_allocated', [
            'patient' => $admission->patient?->full_name ?? (string) $admission->patient_id,
            'bed_code' => $allocation->bed?->bed_code ?? '—',
            'admission_number' => $admission->admission_number,
        ]);
    }

    /**
     * Deliberately not Spatie's User::role() scope — it throws RoleDoesNotExist if any listed
     * role name isn't defined yet (e.g. before IpdSeeder/PermissionSeeder has run for an organization),
     * which would crash this listener during ordinary admission/allocation requests. whereHas
     * against the pivot is the same query with none of that fragility — zero matching roles is
     * just zero recipients, never an exception.
     */
    private function coordinatorsFor(int $organizationId)
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ipd_coordinator', 'admission_officer']))
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->get();
    }
}
