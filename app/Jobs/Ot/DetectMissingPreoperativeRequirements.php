<?php

namespace App\Jobs\Ot;

use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Operational indicator only (spec §51's "pending clinical" dashboard section) — never blocks or
 * auto-cancels a scheduled case, only notifies. Idempotent in effect: it only ever sends
 * notifications, never mutates surgery state.
 */
class DetectMissingPreoperativeRequirements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        OtSurgery::query()
            ->where('status', OtSurgery::STATUS_SCHEDULED)
            ->whereBetween('scheduled_start_at', [now(), now()->addHours(24)])
            ->with(['patient'])
            ->each(function (OtSurgery $surgery) use ($notifications) {
                $missing = [];

                if (! $surgery->consent_id) {
                    $missing[] = 'consent';
                }

                if (! $surgery->preoperativeAssessment || $surgery->preoperativeAssessment->status !== 'final') {
                    $missing[] = 'preoperative assessment';
                }

                if (! $surgery->preoperativeChecklist || $surgery->preoperativeChecklist->status !== 'completed') {
                    $missing[] = 'preoperative checklist';
                }

                if (empty($missing)) {
                    return;
                }

                $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ot_coordinator', 'ot_nurse', 'ot_manager']))
                    ->whereHas('companies', fn ($q) => $q->where('companies.id', $surgery->company_id))
                    ->get();

                if ($recipients->isEmpty()) {
                    return;
                }

                $notifications->send($recipients, 'ot_missing_preoperative_requirement', [
                    'patient' => $surgery->patient?->full_name ?? (string) $surgery->patient_id,
                    'surgery_number' => $surgery->surgery_number,
                    'missing' => implode(', ', $missing),
                ]);
            });
    }
}
