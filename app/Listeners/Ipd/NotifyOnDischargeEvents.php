<?php

namespace App\Listeners\Ipd;

use App\Events\Ipd\DischargeRequested;
use App\Events\Ipd\PatientDischarged;
use App\Models\User;
use App\Services\NotificationService;

class NotifyOnDischargeEvents
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handleRequested(DischargeRequested $event): void
    {
        $request = $event->request;
        $recipients = $this->coordinatorsFor($request->company_id);

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ipd_discharge_requested', [
            'patient' => $request->patient?->full_name ?? (string) $request->patient_id,
            'admission_number' => $request->admission?->admission_number ?? '—',
        ]);
    }

    public function handleCompleted(PatientDischarged $event): void
    {
        $admission = $event->admission;
        $recipients = $this->coordinatorsFor($admission->company_id);

        if ($recipients->isEmpty()) {
            return;
        }

        $this->notifications->send($recipients, 'ipd_discharge_completed', [
            'patient' => $admission->patient?->full_name ?? (string) $admission->patient_id,
            'admission_number' => $admission->admission_number,
        ]);
    }

    /**
     * Deliberately not Spatie's User::role() scope — it throws RoleDoesNotExist if any listed
     * role name isn't defined yet, which would crash this listener during ordinary discharge
     * request/completion. whereHas against the pivot never throws for a missing role.
     */
    private function coordinatorsFor(int $companyId)
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ipd_coordinator', 'ward_manager']))
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
    }
}
