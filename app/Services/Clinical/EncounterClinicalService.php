<?php

namespace App\Services\Clinical;

use App\Models\Encounter;
use App\Models\EncounterComplaint;
use App\Models\EncounterHistory;
use App\Models\EncounterExamination;
use App\Models\EncounterReviewOfSystem;
use App\Models\PatientProblem;
use App\Models\EncounterProcedure;
use App\Models\ClinicalOrder;
use App\Models\ClinicalOrderItem;
use App\Models\EncounterReferral;
use App\Models\EncounterInstruction;
use App\Models\EncounterNote;
use App\Models\EncounterDocument;
use App\Models\EncounterAmendment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EncounterClinicalService
{
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
            $company = $encounter->company;
            $prefix = strtoupper(substr($company->code, 0, 3));
            $last = ClinicalOrder::where('company_id', $company->id)->orderByDesc('id')->first();
            $sequence = $last ? $last->id + 1 : 1;

            $order = ClinicalOrder::create([
                'company_id' => $encounter->company_id,
                'branch_id' => $encounter->branch_id,
                'order_number' => sprintf('%s-ORD-%08d', $prefix, $sequence),
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
                $order->items()->create(array_merge($itemData, ['company_id' => $company->id]));
            }

            return $order;
        });
    }

    public function addReferral(Encounter $encounter, array $data, ?User $user = null): EncounterReferral
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->referrals()->create([
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

    public function createAmendment(Encounter $encounter, array $data, ?User $user = null): EncounterAmendment
    {
        return DB::transaction(function () use ($encounter, $data, $user) {
            return $encounter->amendments()->create([
                'patient_id' => $encounter->patient_id,
                'company_id' => $encounter->company_id,
                'amendment_type' => $data['amendment_type'],
                'reason' => $data['reason'],
                'content' => $data['content'],
                'created_by' => $user?->id ?? auth()->id(),
                'approved_by' => $data['approved_by'] ?? null,
                'approved_at' => $data['approved_at'] ?? null,
            ]);
        });
    }
}
