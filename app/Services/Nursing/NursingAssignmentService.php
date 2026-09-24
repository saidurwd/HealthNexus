<?php

namespace App\Services\Nursing;

use App\Events\Nursing\NurseAssigned;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Append-only assignment history (spec §8) — assigning a nurse to a scope that already has an
 * active assignment of the same type ends the prior row rather than overwriting it, mirroring
 * IpdProviderAssignmentService's pattern.
 */
class NursingAssignmentService
{
    public function assign(NursingEpisode $episode, array $data, User $user): NursingAssignment
    {
        $assignment = DB::transaction(function () use ($episode, $data, $user) {
            $type = $data['assignment_type'] ?? NursingAssignment::TYPE_PATIENT;

            NursingAssignment::query()
                ->where('episode_id', $episode->id)
                ->where('assignment_type', $type)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $assignment = NursingAssignment::create([
                ...$data,
                'episode_id' => $episode->id,
                'admission_id' => $episode->admission_id,
                'patient_id' => $episode->patient_id,
                'assignment_type' => $type,
                'started_at' => $data['started_at'] ?? now(),
                'assigned_by' => $user->id,
            ]);

            if ($type === NursingAssignment::TYPE_PATIENT || $episode->primary_nurse_id === null) {
                $episode->update(['primary_nurse_id' => $assignment->nurse_id]);
            }

            return $assignment;
        });

        event(new NurseAssigned($assignment));

        return $assignment;
    }

    public function end(NursingAssignment $assignment, User $user): NursingAssignment
    {
        $assignment->update(['ended_at' => now()]);

        return $assignment->refresh();
    }

    public function currentFor(NursingEpisode $episode, string $assignmentType): ?NursingAssignment
    {
        return NursingAssignment::query()
            ->where('episode_id', $episode->id)
            ->where('assignment_type', $assignmentType)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }
}
