<?php

namespace App\Services\Ed;

use App\Events\Ed\EdPatientArrived;
use App\Events\Ed\EdTreatmentStarted;
use App\Models\Branch;
use App\Models\Ed\EdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Services\EncounterService;
use App\Services\Numbering\NumberSeriesService;
use App\Services\Patients\PatientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Arrival, rapid registration (including the unknown patient), treatment start. Owns the ED visit; never a patient or encounter table. */
class EdVisitService
{
    public function __construct(
        private readonly PatientService $patients,
        private readonly EncounterService $encounters,
        private readonly NumberSeriesService $numbers,
        private readonly TriageScale $scale,
    ) {}

    /**
     * @param  array{patient_id?: int|null, arrival_mode?: string, chief_complaint?: ?string, sex?: ?string, estimated_age?: ?int, description?: ?string, is_mass_casualty?: bool}  $data
     *                                                                                                                                                                                     with no patient_id an UNKNOWN patient is registered (temporary identity) — it is merged later through the Patient merge workflow
     */
    public function arrive(User $user, Branch $branch, array $data): EdVisit
    {
        $mode = $data['arrival_mode'] ?? 'walk_in';
        if (! in_array($mode, config('ed.arrival_modes'), true)) {
            throw ValidationException::withMessages(['arrival_mode' => 'Unknown arrival mode.']);
        }

        return DB::transaction(function () use ($user, $branch, $data, $mode) {
            $unknown = empty($data['patient_id']);
            $patient = $unknown ? $this->registerUnknown($user, $branch, $data) : Patient::query()->where('organization_id', $branch->organization_id)->findOrFail($data['patient_id']);

            $this->assertNoActiveVisit($patient);

            $encounter = $this->encounters->createEncounter([
                'organization_id' => $branch->organization_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
                'encounter_type' => 'Emergency',
            ], $user);

            $arrival = now();
            $visit = EdVisit::create([
                'organization_id' => $branch->organization_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
                'visit_number' => $this->numbers->next('ed.visit', $branch->organization_id, $branch->id, prefix: 'EDV', format: '{PREFIX}-{YEAR}-{SEQ:8}', userId: $user->id),
                'arrival_at' => $arrival, 'arrival_mode' => $mode, 'chief_complaint' => $data['chief_complaint'] ?? null,
                'status' => EdVisit::AWAITING_TRIAGE, 'triage_due_at' => $arrival->copy()->addMinutes($this->scale->doorToTriageMinutes()),
                'is_unknown_patient' => $unknown, 'is_mass_casualty' => (bool) ($data['is_mass_casualty'] ?? false), 'created_by' => $user->id,
            ]);

            $this->recordStatus($visit, null, EdVisit::AWAITING_TRIAGE, $user, $unknown ? 'Unknown patient registered' : 'Arrived');

            EdPatientArrived::dispatch($visit->id, $visit->organization_id, $visit->patient_id);

            return $visit->refresh();
        });
    }

    /** First contact with a doctor stops the door-to-doctor clock. */
    public function startTreatment(EdVisit $visit, User $doctor): EdVisit
    {
        return DB::transaction(function () use ($visit, $doctor) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);

            if (! in_array($locked->status, [EdVisit::WAITING, EdVisit::AWAITING_TRIAGE, EdVisit::OBSERVATION], true)) {
                throw ValidationException::withMessages(['visit' => "Treatment cannot start from status [{$locked->status}]."]);
            }

            $from = $locked->status;
            $locked->update([
                'status' => EdVisit::IN_TREATMENT, 'doctor_id' => $doctor->id, 'doctor_seen_at' => $locked->doctor_seen_at ?? now(), 'retriage_due_at' => null,
            ]);
            $this->recordStatus($locked, $from, EdVisit::IN_TREATMENT, $doctor, 'Doctor started treatment');

            EdTreatmentStarted::dispatch($locked->id, $locked->organization_id, $locked->patient_id);

            return $locked->refresh();
        });
    }

    public function assignTeam(EdVisit $visit, ?int $doctorId, ?int $nurseId): EdVisit
    {
        foreach (array_filter([$doctorId, $nurseId]) as $userId) {
            if (! User::query()->whereKey($userId)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $visit->organization_id))->exists()) {
                throw ValidationException::withMessages(['team' => 'That person does not belong to this organization.']);
            }
        }

        $visit->update(['doctor_id' => $doctorId ?? $visit->doctor_id, 'nurse_id' => $nurseId ?? $visit->nurse_id]);

        return $visit->refresh();
    }

    public function recordStatus(EdVisit $visit, ?string $from, string $to, ?User $by, ?string $note = null): void
    {
        $visit->statusHistory()->create(['from_status' => $from, 'to_status' => $to, 'changed_by' => $by?->id, 'changed_at' => now(), 'note' => $note]);
    }

    private function registerUnknown(User $user, Branch $branch, array $data): Patient
    {
        $label = $this->numbers->next('ed.unknown', $branch->organization_id, null, prefix: 'UNKNOWN', format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $user->id);

        return $this->patients->createPatient(array_filter([
            'organization_id' => $branch->organization_id, 'first_name' => 'Unknown', 'last_name' => $label,
            'sex' => $data['sex'] ?? null, 'dob_unknown' => true, 'estimated_age' => $data['estimated_age'] ?? null, 'estimated_age_unit' => isset($data['estimated_age']) ? 'years' : null,
            'is_unknown' => true, 'is_temporary' => true, 'notes' => $data['description'] ?? null,
        ], fn ($v) => $v !== null), $user, $branch);
    }

    private function assertNoActiveVisit(Patient $patient): void
    {
        if (EdVisit::query()->where('patient_id', $patient->id)->active()->exists()) {
            throw ValidationException::withMessages(['patient_id' => 'This patient already has an open emergency visit.']);
        }
    }
}
