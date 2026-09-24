<?php

namespace App\Services\Nursing\Fhir;

use App\Models\Nursing\NursingTask;

class TaskFhirMapper
{
    public function toTask(NursingTask $task): array
    {
        return [
            'resourceType' => 'Task',
            'id' => (string) $task->id,
            'status' => match ($task->status) {
                NursingTask::STATUS_COMPLETED => 'completed',
                NursingTask::STATUS_IN_PROGRESS => 'in-progress',
                NursingTask::STATUS_CANCELLED, NursingTask::STATUS_SKIPPED => 'cancelled',
                NursingTask::STATUS_REFUSED => 'rejected',
                default => 'requested',
            },
            'intent' => 'order',
            'description' => $task->task_type,
            'for' => ['reference' => "Patient/{$task->patient_id}"],
            'executionPeriod' => ['end' => $task->due_at?->toAtomString()],
        ];
    }
}
