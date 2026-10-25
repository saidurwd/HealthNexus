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
 * Notifies the assigned surgical team about tomorrow's cases — never the patient directly from
 * this job (no patient contact channel is wired in this phase); only ever sends notifications.
 */
class SendSurgeryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        OtSurgery::query()
            ->where('status', OtSurgery::STATUS_SCHEDULED)
            ->whereBetween('scheduled_start_at', [now()->addHours(20), now()->addHours(28)])
            ->with(['patient', 'currentTeam.provider.user'])
            ->each(function (OtSurgery $surgery) use ($notifications) {
                $recipients = $surgery->currentTeam
                    ->map(fn ($team) => $team->provider?->user)
                    ->filter()
                    ->unique('id');

                if ($recipients->isEmpty()) {
                    return;
                }

                $notifications->send($recipients, 'ot_surgery_reminder', [
                    'patient' => $surgery->patient?->full_name ?? (string) $surgery->patient_id,
                    'surgery_number' => $surgery->surgery_number,
                    'scheduled_start_at' => $surgery->scheduled_start_at->toDateTimeString(),
                ]);
            });
    }
}
