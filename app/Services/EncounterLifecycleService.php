<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\EncounterStatusHistory;
use App\Models\User;
use App\Events\Clinical\EncounterStarted;
use App\Events\Clinical\EncounterCompleted;
use App\Events\Clinical\EncounterLocked;
use Illuminate\Support\Facades\DB;

class EncounterLifecycleService
{
    private array $transitions = [
        'draft' => ['registered'],
        'registered' => ['waiting', 'cancelled'],
        'waiting' => ['in_progress', 'cancelled', 'transferred'],
        'in_progress' => ['paused', 'completed', 'transferred'],
        'paused' => ['in_progress'],
        'completed' => ['locked'],
        'cancelled' => [],
        'transferred' => [],
        'locked' => ['amended'],
        'amended' => ['locked'],
    ];

    public function canTransition(Encounter $encounter, string $newStatus): bool
    {
        $current = $encounter->status;

        if ($current === $newStatus) {
            return true;
        }

        return in_array($newStatus, $this->transitions[$current] ?? []);
    }

    public function register(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'registered', $user, null, 'Encounter registered.');
    }

    public function start(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'in_progress', $user, null, 'Encounter started.');
    }

    public function pause(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'paused', $user, null, 'Encounter paused.');
    }

    public function resume(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'in_progress', $user, null, 'Encounter resumed.');
    }

    public function complete(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'completed', $user, null, 'Encounter completed.');
    }

    public function cancel(Encounter $encounter, ?string $reason = null, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'cancelled', $user, $reason, 'Encounter cancelled.');
    }

    public function transfer(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'transferred', $user, null, 'Encounter transferred.');
    }

    public function lock(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'locked', $user, null, 'Encounter locked.');
    }

    public function amend(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'amended', $user, null, 'Encounter amended.');
    }

    public function unamend(Encounter $encounter, ?User $user = null): Encounter
    {
        return $this->moveTo($encounter, 'locked', $user, null, 'Encounter re-locked after amendment.');
    }

    public function moveTo(Encounter $encounter, string $newStatus, ?User $user = null, ?string $reason = null, ?string $notes = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $newStatus, $user, $reason, $notes) {
            $this->guardTransition($encounter, $newStatus);

            // Captured before update() mutates the same in-memory instance — the previous
            // implementation read $encounter->status for from_status *after* calling update(),
            // so every history row logged from_status === to_status.
            $previousStatus = $encounter->status;

            $updates = ['status' => $newStatus];

            if ($newStatus === 'in_progress' && ! $encounter->started_at) {
                $updates['started_at'] = now();
            }

            if (in_array($newStatus, ['completed', 'cancelled', 'transferred'])) {
                $updates['ended_at'] = now();
            }

            if ($newStatus === 'completed') {
                $updates['completed_at'] = now();
                $updates['completed_by'] = $user?->id ?? auth()->id();
                // Auto-lock on completion (spec §38/§73: "When doctor completes the encounter:
                // status = completed, locked_at = now()") — locking was previously a separate,
                // skippable manual step, leaving every completed encounter fully editable until
                // someone remembered to call lock() explicitly.
                $updates['locked_at'] = now();
                $updates['locked_by'] = $user?->id ?? auth()->id();
            }

            if ($newStatus === 'locked') {
                $updates['locked_at'] = now();
                $updates['locked_by'] = $user?->id ?? auth()->id();
            }

            $encounter->update($updates);

            $this->recordHistory($encounter, $previousStatus, $newStatus, $user, $reason, $notes);

            $eventClass = match ($newStatus) {
                'in_progress' => EncounterStarted::class,
                'completed' => EncounterCompleted::class,
                'locked' => EncounterLocked::class,
                default => null,
            };

            if ($eventClass) {
                event(new $eventClass($encounter));
            }

            return $encounter;
        });
    }

    private function guardTransition(Encounter $encounter, string $newStatus): void
    {
        if (! $this->canTransition($encounter, $newStatus)) {
            throw new \InvalidArgumentException("Invalid status transition from {$encounter->status} to {$newStatus}.");
        }
    }

    private function recordHistory(Encounter $encounter, string $previousStatus, string $newStatus, ?User $user, ?string $reason, ?string $notes): EncounterStatusHistory
    {
        return $encounter->statusHistory()->create([
            'from_status' => $previousStatus,
            'to_status' => $newStatus,
            'changed_by' => $user?->id ?? auth()->id(),
            'changed_at' => now(),
            'reason' => $reason,
            'notes' => $notes,
            'metadata' => [],
        ]);
    }
}
