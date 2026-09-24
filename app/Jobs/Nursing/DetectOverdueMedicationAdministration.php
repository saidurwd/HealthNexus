<?php

namespace App\Jobs\Nursing;

use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Flips scheduled -> due once past due time, and notifies — never auto-cancels or auto-actions a
 * dose. Idempotent: the "due" transition only fires once (query only matches rows still
 * 'scheduled'); rows already 'due' are re-notified but not re-transitioned.
 */
class DetectOverdueMedicationAdministration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        NursingMedicationAdministration::query()
            ->where('status', NursingMedicationAdministration::STATUS_SCHEDULED)
            ->where('scheduled_at', '<', now())
            ->update(['status' => NursingMedicationAdministration::STATUS_DUE]);

        NursingMedicationAdministration::query()
            ->where('status', NursingMedicationAdministration::STATUS_DUE)
            ->with(['patient', 'medication', 'admission'])
            ->each(function (NursingMedicationAdministration $mar) use ($notifications) {
                $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['charge_nurse', 'nursing_supervisor']))
                    ->whereHas('companies', fn ($q) => $q->where('companies.id', $mar->company_id))
                    ->get();

                if ($recipients->isEmpty()) {
                    return;
                }

                $notifications->send($recipients, 'nursing_medication_overdue', [
                    'medication' => $mar->medication?->name ?? 'Medication',
                    'patient' => $mar->patient?->full_name ?? (string) $mar->patient_id,
                    'admission_number' => $mar->admission?->admission_number ?? '—',
                    'scheduled_at' => $mar->scheduled_at->toDateTimeString(),
                ]);
            });
    }
}
