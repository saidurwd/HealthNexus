<?php

namespace Tests\Feature\Clinical;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinical\EncounterClinicalService;
use App\Services\EncounterLifecycleService;
use App\Services\EncounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_and_completing_an_encounter_populates_the_patient_timeline(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);

        $user->companies()->attach($company->id, ['access_level' => 'admin']);

        $encounter = app(EncounterService::class)->createEncounter([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
        ], $user);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'ENCOUNTER_CREATED',
            'subject_id' => $encounter->id,
        ]);

        app(EncounterLifecycleService::class)->moveTo($encounter->fresh(), 'waiting', $user);
        app(EncounterLifecycleService::class)->moveTo($encounter->fresh(), 'in_progress', $user);
        app(EncounterLifecycleService::class)->complete($encounter->fresh(), $user);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'ENCOUNTER_COMPLETED',
            'subject_id' => $encounter->id,
        ]);
    }

    public function test_adding_a_diagnosis_populates_the_patient_timeline(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $patient = Patient::factory()->create(['company_id' => $company->id]);

        $encounter = Encounter::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'patient_id' => $patient->id,
        ]);

        $diagnosis = app(EncounterClinicalService::class)->addDiagnosis($encounter, [
            'description' => 'Type 2 diabetes mellitus',
        ], $user);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'DIAGNOSIS_ADDED',
            'subject_id' => $diagnosis->id,
        ]);
    }
}
