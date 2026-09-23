<?php

namespace Tests\Feature\Patients;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientAddress;
use App\Models\PatientGuardian;
use App\Models\User;
use App\Services\Patients\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the merge-safety fix: previously the duplicate was hard-deleted after
 * a ' (merged)' name mangle and only 7 relations were transferred, silently orphaning a patient's
 * clinical history (encounters, appointments, vitals, diagnoses, prescriptions, orders) on merge.
 */
class PatientMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_soft_deletes_duplicate_and_marks_merged_into(): void
    {
        $company = Company::factory()->create();
        $master = Patient::factory()->create(['company_id' => $company->id]);
        $duplicate = Patient::factory()->create(['company_id' => $company->id, 'first_name' => 'Original']);

        app(PatientService::class)->mergePatients($master, $duplicate);

        $duplicate->refresh();
        $this->assertSame('merged', $duplicate->status);
        $this->assertSame($master->id, $duplicate->merged_into_patient_id);
        $this->assertSoftDeleted('patients', ['id' => $duplicate->id]);
        // The old implementation appended " (merged)" to the name — confirm that's gone.
        $this->assertSame('Original', $duplicate->first_name);
    }

    public function test_merge_transfers_clinical_relations_to_the_master(): void
    {
        $company = Company::factory()->create();
        $master = Patient::factory()->create(['company_id' => $company->id]);
        $duplicate = Patient::factory()->create(['company_id' => $company->id]);

        $encounter = Encounter::factory()->create(['patient_id' => $duplicate->id, 'company_id' => $company->id]);
        $appointment = Appointment::factory()->create(['patient_id' => $duplicate->id, 'company_id' => $company->id]);
        $address = PatientAddress::factory()->create(['patient_id' => $duplicate->id, 'company_id' => $company->id]);
        $guardian = PatientGuardian::factory()->create(['patient_id' => $duplicate->id, 'company_id' => $company->id]);

        app(PatientService::class)->mergePatients($master, $duplicate);

        $this->assertSame($master->id, $encounter->fresh()->patient_id);
        $this->assertSame($master->id, $appointment->fresh()->patient_id);
        $this->assertSame($master->id, $address->fresh()->patient_id);
        $this->assertSame($master->id, $guardian->fresh()->patient_id);
    }

    public function test_merge_records_a_timeline_event_on_the_master(): void
    {
        $company = Company::factory()->create();
        $master = Patient::factory()->create(['company_id' => $company->id]);
        $duplicate = Patient::factory()->create(['company_id' => $company->id]);
        $actor = User::factory()->create();

        app(PatientService::class)->mergePatients($master, $duplicate, $actor);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $master->id,
            'event_type' => 'PATIENT_MERGED',
        ]);
    }
}
