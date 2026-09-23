<?php

namespace App\Services;

use App\Events\Clinical\EncounterCreated;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\EncounterType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EncounterService
{
    public function __construct(private EncounterLifecycleService $lifecycle) {}

    public function createEncounter(array $data, User $user): Encounter
    {
        return DB::transaction(function () use ($data, $user): Encounter {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['encounter_no'] = $this->generateEncounterNo($company);
            $data['created_by'] = $user->id;
            $data['status'] = 'registered';

            if (empty($data['encounter_date'])) {
                $data['encounter_date'] = now()->toDateString();
            }

            // encounter_type is a NOT NULL legacy display column; encounter_type_id (the
            // configurable master-data table) is the field new code should prefer.
            if (empty($data['encounter_type'])) {
                $data['encounter_type'] = ! empty($data['encounter_type_id'])
                    ? (EncounterType::find($data['encounter_type_id'])?->name ?? 'OPD')
                    : 'OPD';
            }

            $encounter = Encounter::create($data);

            $encounter->statusHistory()->create([
                'from_status' => null,
                'to_status' => 'registered',
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => 'Encounter created.',
                'metadata' => [],
            ]);

            event(new EncounterCreated($encounter));

            return $encounter;
        });
    }

    /**
     * Idempotent: an appointment that already has an encounter (e.g. the doctor re-opens the
     * consultation screen) returns the existing one rather than creating a duplicate — this is
     * the integration point that makes the OPD consultation flow actually create/drive a real
     * Encounter instead of operating on the Appointment alone (spec's "Appointment ≠ Encounter"
     * rule).
     */
    public function findOrCreateFromAppointment(Appointment $appointment, User $user): Encounter
    {
        $existing = Encounter::where('appointment_id', $appointment->id)->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($appointment, $user) {
            $encounter = $this->createEncounter([
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'provider_id' => $appointment->provider_id,
                'department_id' => $appointment->room?->department_id,
                'specialty_id' => $appointment->specialty_id,
                'encounter_type_id' => $appointment->appointment_type_id,
                'encounter_date' => $appointment->appointment_date?->toDateString(),
                'priority' => $appointment->priority ?? 'routine',
                'source' => $appointment->is_walk_in ? 'walk_in' : 'appointment',
                'reason_for_visit' => $appointment->reason,
            ], $user);

            $this->lifecycle->moveTo($encounter, 'waiting', $user, null, 'Patient in queue.');
            $this->lifecycle->moveTo($encounter, 'in_progress', $user, null, 'Consultation started.');

            return $encounter->fresh();
        });
    }

    /**
     * status is deliberately excluded — every status change must go through
     * EncounterLifecycleService so the transition graph and history are enforced. Callers that
     * need to change status extract it first and call the lifecycle service directly (see
     * Modules\Clinical\Http\Controllers\EncounterController::update()).
     */
    public function updateEncounter(Encounter $encounter, array $data): Encounter
    {
        unset($data['status']);

        $encounter->update($data);

        return $encounter;
    }

    private function generateEncounterNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));

        $counter = DB::table('encounter_counters')
            ->where('company_id', $company->id)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
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
