<?php

namespace App\Jobs\Ipd;

use App\Models\Ipd\IpdAdmission;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Operational indicator only (spec §43) — never an automatic clinical judgment or action.
 * Notifies IPD coordination roles for every active admission whose expected discharge date has
 * passed; idempotent in effect since it only ever sends a notification, never mutates admission
 * state.
 */
class DetectDelayedDischarges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        $today = now()->toDateString();

        IpdAdmission::query()
            ->where('status', 'active')
            ->whereNotNull('expected_discharge_date')
            ->where('expected_discharge_date', '<', $today)
            ->with(['patient', 'currentAllocation.bed.room.ward', 'attendingProvider'])
            ->each(function (IpdAdmission $admission) use ($notifications) {
                // whereHas against the roles pivot, not Spatie's role() scope — that throws
                // RoleDoesNotExist if a listed role isn't defined yet for a given deployment.
                $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ipd_coordinator', 'ward_manager', 'hospital_admin']))
                    ->whereHas('companies', fn ($q) => $q->where('companies.id', $admission->company_id))
                    ->get();

                if ($recipients->isEmpty()) {
                    return;
                }

                $daysOverdue = (int) now()->startOfDay()->diffInDays($admission->expected_discharge_date->startOfDay());

                $notifications->send($recipients, 'ipd_delayed_discharge', [
                    'patient' => $admission->patient?->full_name ?? (string) $admission->patient_id,
                    'admission_number' => $admission->admission_number,
                    'ward' => $admission->currentAllocation?->bed?->room?->ward?->name ?? '—',
                    'days_overdue' => (string) $daysOverdue,
                ]);
            });
    }
}
