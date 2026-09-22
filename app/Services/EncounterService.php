<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Encounter;
use App\Models\EncounterStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EncounterService
{
    public function createEncounter(array $data, User $user): Encounter
    {
        return DB::transaction(function () use ($data, $user): Encounter {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['encounter_no'] = $this->generateEncounterNo($company);
            $data['created_by'] = $user->id;

            if (empty($data['encounter_date'])) {
                $data['encounter_date'] = now()->toDateString();
            }

            $encounter = Encounter::create($data);

            $this->recordHistory($encounter, $data['status'] ?? 'registered', $user, null, 'Encounter created.');

            return $encounter;
        });
    }

    public function updateEncounter(Encounter $encounter, array $data): Encounter
    {
        $encounter->update($data);

        return $encounter;
    }

    public function startEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $this->recordHistory($encounter, 'in_progress', $user, null, 'Encounter started.');

            return $encounter;
        });
    }

    public function pauseEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'paused',
            ]);

            $this->recordHistory($encounter, 'paused', $user, null, 'Encounter paused.');

            return $encounter;
        });
    }

    public function resumeEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'in_progress',
            ]);

            $this->recordHistory($encounter, 'in_progress', $user, null, 'Encounter resumed.');

            return $encounter;
        });
    }

    public function completeEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'completed',
                'ended_at' => now(),
                'completed_at' => now(),
                'completed_by' => $user?->id ?? auth()->id(),
            ]);

            $this->recordHistory($encounter, 'completed', $user, null, 'Encounter completed.');

            return $encounter;
        });
    }

    public function lockEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by' => $user?->id ?? auth()->id(),
            ]);

            $this->recordHistory($encounter, 'locked', $user, null, 'Encounter locked.');

            return $encounter;
        });
    }

    public function cancelEncounter(Encounter $encounter, ?string $reason = null, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $reason, $user) {
            $encounter->update([
                'status' => 'cancelled',
                'ended_at' => now(),
            ]);

            $this->recordHistory($encounter, 'cancelled', $user, $reason, 'Encounter cancelled.');

            return $encounter;
        });
    }

    public function transferEncounter(Encounter $encounter, ?User $user = null): Encounter
    {
        return DB::transaction(function () use ($encounter, $user) {
            $encounter->update([
                'status' => 'transferred',
                'ended_at' => now(),
            ]);

            $this->recordHistory($encounter, 'transferred', $user, null, 'Encounter transferred.');

            return $encounter;
        });
    }

    public function recordHistory(Encounter $encounter, string $newStatus, ?User $user = null, ?string $reason = null, ?string $notes = null): EncounterStatusHistory
    {
        return $encounter->statusHistory()->create([
            'old_status' => $encounter->status,
            'new_status' => $newStatus,
            'changed_by' => $user?->id ?? auth()->id(),
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    private function generateEncounterNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));

        $counter = DB::table('encounter_counters')
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->first();

        if (!$counter) {
            DB::table('encounter_counters')->insert([
                'company_id' => $company->id,
                'counter' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return sprintf('%s-ENC-%08d', $prefix, 1);
        }

        $next = $counter->counter + 1;

        DB::table('encounter_counters')
            ->where('company_id', $company->id)
            ->update(['counter' => $next, 'updated_at' => now()]);

        return sprintf('%s-ENC-%08d', $prefix, $next);
    }
}
