<?php

namespace App\Jobs\Nursing;

use App\Models\Nursing\NursingTask;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Idempotent: only ever moves a task from pending/in_progress to overdue once (the query only
 * ever matches rows not already in that status), and only ever sends notifications — never
 * completes, skips, or otherwise mutates the task's clinical outcome.
 */
class DetectOverdueNursingTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        NursingTask::query()
            ->whereIn('status', [NursingTask::STATUS_PENDING, NursingTask::STATUS_IN_PROGRESS])
            ->where('due_at', '<', now())
            ->with(['patient', 'assignedNurse', 'admission'])
            ->each(function (NursingTask $task) use ($notifications) {
                $task->update(['status' => NursingTask::STATUS_OVERDUE]);

                $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['charge_nurse', 'nursing_supervisor']))
                    ->whereHas('companies', fn ($q) => $q->where('companies.id', $task->company_id))
                    ->get();

                if ($task->assignedNurse) {
                    $recipients->push($task->assignedNurse);
                }

                if ($recipients->isEmpty()) {
                    return;
                }

                $notifications->send($recipients, 'nursing_task_overdue', [
                    'task_type' => $task->task_type,
                    'patient' => $task->patient?->full_name ?? (string) $task->patient_id,
                    'admission_number' => $task->admission?->admission_number ?? '—',
                    'due_at' => $task->due_at->toDateTimeString(),
                ]);
            });
    }
}
