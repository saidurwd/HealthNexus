<?php

namespace App\Services\Clinical;

use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\EncounterComplaint;
use App\Models\EncounterHistory;
use App\Models\EncounterExamination;
use App\Models\EncounterReviewOfSystem;
use App\Models\PatientProblem;
use App\Models\EncounterProcedure;
use App\Models\ClinicalOrder;
use App\Models\Prescription;
use App\Models\EncounterReferral;
use App\Models\EncounterInstruction;
use App\Models\EncounterNote;
use App\Models\EncounterDocument;
use App\Models\EncounterAmendment;
use App\Models\User;
use App\Models\VitalSign;
use App\Events\Clinical\ClinicalOrderCreated;
use App\Events\Clinical\DiagnosisAdded;
use App\Events\Clinical\PrescriptionCreated;
use App\Events\Clinical\ReferralCreated;
use App\Events\Clinical\ClinicalAmendmentCreated;
use App\Events\Clinical\VitalRecorded;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EncounterClinicalService
{
    public function __construct(private ClinicalNumberGenerator $numberGenerator) {}

    /**
     * Vitals are append-only — every call inserts a new row, never updates a previous one, so
     * the clinical workspace can show a full trend history (spec §21/§22: "Never overwrite
     * previous measurements. Every measurement is a clinical observation.").
     */
    public function addVital(Encounter $encounter, array $data, ?User $user = null): VitalSign
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $vital = VitalSign::create([
                'company_id' => $encounter->company_id,
                'branch_id' => $encounter->branch_id,
                'appointment_id' => $encounter->appointment_id,
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'recorded_by' => $user?->id ?? auth()->id(),
                'recorded_at' => now(),
                'temperature' => $data['temperature'] ?? null,
                'temperature_unit' => $data['temperature_unit'] ?? 'celsius',
                'systolic' => $data['systolic'] ?? null,
                'diastolic' => $data['diastolic'] ?? null,
                'bp_unit' => $data['bp_unit'] ?? 'mmhg',
                'pulse_rate' => $data['pulse_rate'] ?? null,
                'respiratory_rate' => $data['respiratory_rate'] ?? null,
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'bmi' => $this->calculateBmi($data['height'] ?? null, $data['weight'] ?? null),
                'oxygen_saturation' => $data['oxygen_saturation'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            VitalRecorded::dispatch($encounter, $vital);

            return $vital;
        });
    }

    /**
     * BMI is calculated for display convenience but never replaces the underlying height/weight
     * measurements (spec §18: "BMI may be calculated but should not replace the underlying
     * height/weight measurements").
     */
    private function calculateBmi(?float $heightCm, ?float $weightKg): ?float
    {
        if (! $heightCm || ! $weightKg || $heightCm <= 0) {
            return null;
        }

        $heightM = $heightCm / 100;

        return round($weightKg / ($heightM * $heightM), 2);
    }

    public function addDiagnosis(Encounter $encounter, array $data, ?User $user = null): Diagnosis
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $diagnosis = Diagnosis::create([
                'company_id' => $encounter->company_id,
                'branch_id' => $encounter->branch_id,
                'appointment_id' => $encounter->appointment_id,
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'doctor_id' => $data['doctor_id'] ?? $encounter->provider_id ?? ($user?->id ?? auth()->id()),
                'code_type' => $data['code_type'] ?? null,
                'coding_system' => $data['coding_system'] ?? null,
                'code' => $data['code'] ?? null,
                'description' => $data['description'],
                'status' => $data['status'] ?? 'confirmed',
                'diagnosis_type' => $data['diagnosis_type'] ?? 'primary',
                'is_primary' => $data['is_primary'] ?? false,
                'recorded_by' => $user?->id ?? auth()->id(),
                'recorded_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            DiagnosisAdded::dispatch($encounter, $diagnosis);

            return $diagnosis;
        });
    }

    public function createPrescription(Encounter $encounter, array $data, ?User $user = null): Prescription
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $prescription = Prescription::create([
                'company_id' => $encounter->company_id,
                'branch_id' => $encounter->branch_id,
                'appointment_id' => $encounter->appointment_id,
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'doctor_id' => $data['doctor_id'] ?? $encounter->provider_id ?? ($user?->id ?? auth()->id()),
                'prescription_no' => $this->numberGenerator->generatePrescriptionNumber($encounter->company, $encounter->branch_id),
                'status' => 'draft',
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'advice' => $data['advice'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
                'prescribed_at' => now(),
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $prescription->items()->create(array_merge($itemData, ['company_id' => $encounter->company_id]));
            }

            PrescriptionCreated::dispatch($encounter, $prescription);

            return $prescription;
        });
    }
    public function addComplaint(Encounter $encounter, array $data, ?User $user = null): EncounterComplaint
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->complaints()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'complaint' => $data['complaint'],
                'duration' => $data['duration'] ?? null,
                'duration_unit' => $data['duration_unit'] ?? null,
                'onset' => $data['onset'] ?? null,
                'severity' => $data['severity'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function addHistory(Encounter $encounter, array $data, ?User $user = null): EncounterHistory
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->histories()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'history_type' => $data['history_type'],
                'onset' => $data['onset'] ?? null,
                'duration' => $data['duration'] ?? null,
                'course' => $data['course'] ?? null,
                'severity' => $data['severity'] ?? null,
                'associated_symptoms' => $data['associated_symptoms'] ?? null,
                'aggravating_factors' => $data['aggravating_factors'] ?? null,
                'relieving_factors' => $data['relieving_factors'] ?? null,
                'clinical_notes' => $data['clinical_notes'] ?? null,
            ]);
        });
    }

    public function addExamination(Encounter $encounter, array $data, ?User $user = null): EncounterExamination
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->examinations()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'section_name' => $data['section_name'],
                'findings' => $data['findings'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function addReviewOfSystem(Encounter $encounter, array $data, ?User $user = null): EncounterReviewOfSystem
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->reviewOfSystems()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'system_name' => $data['system_name'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function addProblem(Encounter $encounter, array $data, ?User $user = null): PatientProblem
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return PatientProblem::create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'problem_code' => $data['problem_code'] ?? null,
                'problem_name' => $data['problem_name'],
                'coding_system' => $data['coding_system'] ?? null,
                'status' => $data['status'] ?? 'active',
                'onset_date' => $data['onset_date'] ?? null,
                'resolved_date' => $data['resolved_date'] ?? null,
                'source_encounter_id' => $encounter->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
                'updated_by' => $user?->id ?? auth()->id(),
            ]);
        });
    }

    public function addProcedure(Encounter $encounter, array $data, ?User $user = null): EncounterProcedure
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->procedures()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'procedure_code' => $data['procedure_code'] ?? null,
                'procedure_name' => $data['procedure_name'],
                'procedure_date' => $data['procedure_date'] ?? now()->toDateString(),
                'provider_id' => $data['provider_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'recorded_by' => $user?->id ?? auth()->id(),
                'recorded_at' => now(),
            ]);
        });
    }

    public function createOrder(Encounter $encounter, array $data, ?User $user = null): ClinicalOrder
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $order = ClinicalOrder::create([
                'company_id' => $encounter->company_id,
                'branch_id' => $encounter->branch_id,
                'order_number' => $this->numberGenerator->generateOrderNumber($encounter->company, $encounter->branch_id),
                'patient_id' => $encounter->patient_id,
                'encounter_id' => $encounter->id,
                'provider_id' => $data['provider_id'] ?? $user?->id,
                'order_type' => $data['order_type'],
                'priority' => $data['priority'] ?? 'routine',
                'status' => 'requested',
                'ordered_at' => now(),
                'ordered_by' => $user?->id ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $order->items()->create(array_merge($itemData, ['company_id' => $encounter->company_id]));
            }

            event(new ClinicalOrderCreated($encounter, $order));

            return $order;
        });
    }

    public function addReferral(Encounter $encounter, array $data, ?User $user = null): EncounterReferral
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $referral = $encounter->referrals()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'referral_type' => $data['referral_type'],
                'referred_to' => $data['referred_to'] ?? null,
                'referred_by' => $data['referred_by'] ?? null,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            event(new ReferralCreated($encounter, $referral));

            return $referral;
        });
    }

    public function addInstruction(Encounter $encounter, array $data, ?User $user = null): EncounterInstruction
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->instructions()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'instruction_type' => $data['instruction_type'],
                'content' => $data['content'],
                'created_by' => $user?->id ?? auth()->id(),
            ]);
        });
    }

    public function addNote(Encounter $encounter, array $data, ?User $user = null): EncounterNote
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->notes()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'section' => $data['section'] ?? null,
                'content' => $data['content'],
                'created_by' => $user?->id ?? auth()->id(),
            ]);
        });
    }

    public function addDocument(Encounter $encounter, array $data, ?User $user = null): EncounterDocument
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->documents()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'document_type' => $data['document_type'],
                'file_name' => $data['file_name'],
                'file_path' => $data['file_path'],
                'mime_type' => $data['mime_type'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
            ]);
        });
    }

    /**
     * Takes the specific Prescription the caller means (route-model-bound), rather than guessing
     * "whichever draft prescription is latest" — the previous implementation ignored the
     * {prescription} route segment entirely, so if an encounter ever had more than one draft
     * prescription, the wrong one could be issued/cancelled.
     */
    public function issuePrescription(Encounter $encounter, Prescription $prescription, ?User $user = null): Prescription
    {
        $this->assertBelongsToEncounter($encounter, $prescription);

        if ($prescription->status !== 'draft') {
            throw ValidationException::withMessages(['status' => "Only a draft prescription can be issued (current status: {$prescription->status})."]);
        }

        $prescription->update([
            'status' => 'issued',
            'issued_at' => now(),
            'issued_by' => $user?->id ?? auth()->id(),
        ]);

        \App\Events\Clinical\PrescriptionIssued::dispatch($encounter, $prescription);

        return $prescription;
    }

    public function cancelPrescription(Encounter $encounter, Prescription $prescription, ?User $user = null): Prescription
    {
        $this->assertBelongsToEncounter($encounter, $prescription);

        if (! in_array($prescription->status, ['draft', 'issued'], true)) {
            throw ValidationException::withMessages(['status' => "A prescription in '{$prescription->status}' status cannot be cancelled."]);
        }

        $prescription->update(['status' => 'cancelled']);

        return $prescription;
    }

    private function assertBelongsToEncounter(Encounter $encounter, Prescription $prescription): void
    {
        abort_unless($prescription->encounter_id === $encounter->id, 404);
    }

    /**
     * approved_by/approved_at are never accepted from the submitter — a separate
     * approveAmendment() action (requiring a different actor and the encounter.amend.approve
     * permission) is the only path that can set them, closing a self-approval hole where the
     * same request that created the amendment could also mark it approved.
     */
    public function createAmendment(Encounter $encounter, array $data, ?User $user = null): EncounterAmendment
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            $amendment = $encounter->amendments()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'amendment_type' => $data['amendment_type'],
                'reason' => $data['reason'],
                'content' => $data['content'],
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            event(new ClinicalAmendmentCreated($encounter, $amendment));

            return $amendment;
        });
    }

    public function approveAmendment(EncounterAmendment $amendment, User $approver): EncounterAmendment
    {
        if ($amendment->approved_at) {
            throw ValidationException::withMessages(['amendment' => 'This amendment has already been approved.']);
        }

        if ($amendment->created_by === $approver->id) {
            throw ValidationException::withMessages(['amendment' => 'An amendment cannot be approved by the same user who created it.']);
        }

        $amendment->update([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $amendment;
    }
}
