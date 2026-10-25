<?php

namespace App\Jobs\Ot;

use App\Models\Organization;
use App\Models\Ot\OtSurgery;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies OT coordination roles with tomorrow's case count per theatre — the operating list
 * itself is always read live from ot_surgeries (spec §50), this job never caches or materializes
 * it; it only prompts staff to review the live list each morning.
 */
class GenerateOTDailyList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        $tomorrow = now()->addDay()->toDateString();

        Organization::all()->each(function (Organization $organization) use ($tomorrow, $notifications) {
            $count = OtSurgery::where('organization_id', $organization->id)
                ->whereDate('scheduled_date', $tomorrow)
                ->whereNotIn('status', [OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_POSTPONED, OtSurgery::STATUS_REJECTED])
                ->count();

            if ($count === 0) {
                return;
            }

            $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ot_coordinator', 'ot_manager']))
                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            $notifications->send($recipients, 'ot_daily_list_ready', ['date' => $tomorrow, 'count' => (string) $count]);
        });
    }
}
