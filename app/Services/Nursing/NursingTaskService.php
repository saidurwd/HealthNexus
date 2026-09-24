<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NursingTaskCompleted;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * complete()/skip()/refuse()/cancel() all lock the task row inside a transaction and re-verify
 * status under the lock, preventing duplicate completion when two nurses act on the same task
 * concurrently (spec §83).
 */
class NursingTaskService
{
    public function create(NursingEpisode $episode, array $data): NursingTask
    {
        return NursingTask::create([
            ...$data,
            'company_id' => $episode->company_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id,
            'patient_id' => $episode->patient_id,
            'status' => NursingTask::STATUS_PENDING,
        ]);
    }

    public function start(NursingTask $task): NursingTask
    {
        $this->transitionLocked($task->id, [NursingTask::STATUS_PENDING, NursingTask::STATUS_OVERDUE], NursingTask::STATUS_IN_PROGRESS);

        return $task->refresh();
    }

    public function complete(NursingTask $task, User $user, ?string $outcome = null, ?string $notes = null): NursingTask
    {
        DB::transaction(function () use ($task, $user, $outcome, $notes) {
            $locked = NursingTask::query()->lockForUpdate()->findOrFail($task->id);

            if (in_array($locked->status, [NursingTask::STATUS_COMPLETED, NursingTask::STATUS_SKIPPED, NursingTask::STATUS_REFUSED, NursingTask::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages(['task' => "This task is already '{$locked->status}'."]);
            }

            $locked->update([
                'status' => NursingTask::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'outcome' => $outcome,
                'notes' => $notes ?? $locked->notes,
            ]);
        });

        $task->refresh();
        event(new NursingTaskCompleted($task));

        return $task;
    }

    public function skip(NursingTask $task, User $user, string $reason): NursingTask
    {
        return $this->terminate($task, NursingTask::STATUS_SKIPPED, $user, $reason);
    }

    public function refuse(NursingTask $task, User $user, string $reason): NursingTask
    {
        return $this->terminate($task, NursingTask::STATUS_REFUSED, $user, $reason);
    }

    public function cancel(NursingTask $task, User $user, ?string $reason = null): NursingTask
    {
        return $this->terminate($task, NursingTask::STATUS_CANCELLED, $user, $reason);
    }

    private function terminate(NursingTask $task, string $status, User $user, ?string $reason): NursingTask
    {
        DB::transaction(function () use ($task, $status, $user, $reason) {
            $locked = NursingTask::query()->lockForUpdate()->findOrFail($task->id);

            if (in_array($locked->status, [NursingTask::STATUS_COMPLETED, NursingTask::STATUS_SKIPPED, NursingTask::STATUS_REFUSED, NursingTask::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages(['task' => "This task is already '{$locked->status}'."]);
            }

            $locked->update([
                'status' => $status,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'notes' => $reason ?? $locked->notes,
            ]);
        });

        return $task->refresh();
    }

    private function transitionLocked(int $taskId, array $allowedFrom, string $to): void
    {
        DB::transaction(function () use ($taskId, $allowedFrom, $to) {
            $locked = NursingTask::query()->lockForUpdate()->findOrFail($taskId);

            if (! in_array($locked->status, $allowedFrom, true)) {
                throw ValidationException::withMessages(['task' => "Cannot move this task from '{$locked->status}' to '{$to}'."]);
            }

            $locked->update(['status' => $to]);
        });
    }
}
