<?php

namespace App\Jobs\Icu;

use App\Models\Icu\IcuEpisode;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * A safety-net report, not a fixer — flags active episodes with no progress note and no
 * observation in the last 24 hours, mirroring DetectScheduleConflicts's "report, never silently
 * fix" precedent.
 */
class DetectMissingIcuDocumentation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        $episodes = IcuEpisode::query()->whereNull('discharged_at')->with(['patient'])->get();

        foreach ($episodes->groupBy('company_id') as $companyId => $group) {
            $missing = $group->filter(function (IcuEpisode $episode) {
                $hasRecentNote = $episode->progressNotes()->where('created_at', '>=', now()->subDay())->exists();
                $hasRecentObservation = $episode->observations()->where('observed_at', '>=', now()->subDay())->exists();

                return ! $hasRecentNote || ! $hasRecentObservation;
            });

            if ($missing->isEmpty()) {
                continue;
            }

            $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['icu_charge_nurse', 'icu_manager']))
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->get();

            foreach ($missing as $episode) {
                if ($recipients->isEmpty()) {
                    continue;
                }

                $notifications->send($recipients, 'icu_missing_documentation', [
                    'patient' => $episode->patient?->full_name ?? (string) $episode->patient_id,
                    'episode_number' => $episode->episode_number,
                    'missing' => 'progress note or observation in the last 24 hours',
                ]);
            }
        }
    }
}
