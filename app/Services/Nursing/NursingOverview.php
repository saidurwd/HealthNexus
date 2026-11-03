<?php

namespace App\Services\Nursing;

use App\Models\Nursing\NursingAlert;
use App\Models\Nursing\NursingAssessment;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingHandover;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Nursing\NursingTask;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;

/**
 * The figures of the nursing dashboard: what is overdue on the ward and what is mine. A task is overdue when it is still to be done and its time has gone (whether or not the status was updated); a medicine is overdue when it
 * was due and not given. Counts only; the person's own patients are listed on the page, not here.
 */
class NursingOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, int $userId, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now);
        $tasks = fn () => NursingTask::forTenant($organizationId, $branchId);
        $open = [NursingTask::STATUS_PENDING, NursingTask::STATUS_OVERDUE];
        $myEpisodes = NursingAssignment::query()->where('nurse_id', $userId)->whereNull('ended_at')->pluck('episode_id')->unique();

        return [
            'my_patients' => $myEpisodes->count(),
            'ward_patients' => NursingEpisode::forTenant($organizationId, $branchId)->where('status', NursingEpisode::STATUS_ACTIVE)->count(),
            'pending_assessments' => NursingAssessment::forTenant($organizationId, $branchId)->where('status', 'draft')->count(),
            'due_medications' => NursingMedicationAdministration::forTenant($organizationId, $branchId)->whereIn('status', ['scheduled', 'due'])->count(),
            'overdue_medications' => NursingMedicationAdministration::forTenant($organizationId, $branchId)->whereIn('status', ['scheduled', 'due'])->where('scheduled_at', '<', $now)->count(),
            'pending_tasks' => $tasks()->where('status', NursingTask::STATUS_PENDING)->count(),
            'overdue_tasks' => $tasks()->whereIn('status', $open)->where('due_at', '<', $now)->count(),
            'my_overdue_tasks' => $tasks()->whereIn('status', $open)->where('due_at', '<', $now)->where('assigned_nurse_id', $userId)->count(),
            'critical_alerts' => NursingAlert::forTenant($organizationId, $branchId)->whereNull('resolved_at')->whereIn('severity', ['high', 'critical'])->count(),
            'pending_handover' => NursingHandover::forTenant($organizationId, $branchId)->where('status', 'pending_acknowledgement')->count(),
            'tasks' => collect(range(0, 13))->map(function ($i) use ($today, $tasks) {
                $d = $today->subDays(13 - $i);

                return ['day' => $d->format('d M'), 'done' => $tasks()->whereBetween('completed_at', Days::bounds($d))->count(), 'planned' => $tasks()->whereBetween('due_at', Days::bounds($d))->count()];
            })->all(),
        ];
    }
}
