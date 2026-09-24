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
        $recipients = $this->coordinatorsFor($request->company_id);

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
        $recipients = $this->coordinatorsFor($allocation->company_id);

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
     * role name isn't defined yet (e.g. before IpdSeeder/PermissionSeeder has run for a company),
     * which would crash this listener during ordinary admission/allocation requests. whereHas
     * against the pivot is the same query with none of that fragility — zero matching roles is
     * just zero recipients, never an exception.
     */
    private function coordinatorsFor(int $companyId)
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ipd_coordinator', 'admission_officer']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
    }
}
