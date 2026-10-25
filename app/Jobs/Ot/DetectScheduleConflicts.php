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
use Illuminate\Support\Facades\Log;

/**
 * A safety-net report, not a fixer — OtSchedulingService's theatre-row lock should make a real
 * overlap impossible, but this job reports any discrepancy found (mirrors
 * App\Jobs\Ipd\ReconcileBedStates's "report, never silently fix" precedent) rather than assuming
 * the lock is infallible under every deployment/migration path.
 */
class DetectScheduleConflicts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const ACTIVE_STATUSES = [
        OtSurgery::STATUS_SCHEDULED, OtSurgery::STATUS_PREOPERATIVE_PREPARATION,
        OtSurgery::STATUS_READY_FOR_OT, OtSurgery::STATUS_PATIENT_RECEIVED, OtSurgery::STATUS_IN_PROCEDURE,
    ];

    public function handle(NotificationService $notifications): void
    {
        $surgeries = OtSurgery::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotNull('theatre_id')
            ->orderBy('theatre_id')
            ->orderBy('scheduled_start_at')
            ->get();

        $conflictsByCompany = [];

        foreach ($surgeries->groupBy('theatre_id') as $theatreId => $group) {
            $sorted = $group->sortBy('scheduled_start_at')->values();

            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                $current = $sorted[$i];
                $next = $sorted[$i + 1];

                if ($current->scheduled_end_at && $next->scheduled_start_at && $current->scheduled_end_at->gt($next->scheduled_start_at)) {
                    $conflictsByCompany[$current->company_id][] = [$current->id, $next->id, $theatreId];
                }
            }
        }

        if (empty($conflictsByCompany)) {
            return;
        }

        Log::channel('single')->warning('OT schedule conflicts detected', ['conflicts_by_company' => $conflictsByCompany]);

        foreach ($conflictsByCompany as $companyId => $conflicts) {
            $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['ot_coordinator', 'ot_manager', 'hospital_admin']))
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            $notifications->send($recipients, 'ot_schedule_conflict_detected', ['count' => (string) count($conflicts)]);
        }
    }
}
