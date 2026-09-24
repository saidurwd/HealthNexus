<?php

namespace Tests\Feature\Nursing;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyDosageForm;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyRoute;
use App\Models\User;
use Tests\Feature\Ipd\IpdTestCase;

abstract class NursingTestCase extends IpdTestCase
{
    protected function makeAdmission(?Patient $patient = null): IpdAdmission
    {
        $patient ??= Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);

        return IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
    }

    protected function makeEpisode(?IpdAdmission $admission = null): NursingEpisode
    {
        $admission ??= $this->makeAdmission();

        return app(\App\Services\Nursing\NursingEpisodeService::class)->startFromAdmission($admission, $this->user);
    }

    protected function makeNurse(string $role = 'staff_nurse'): User
    {
        $nurse = User::factory()->create();
        $nurse->companies()->attach($this->company->id, ['access_level' => 'staff']);
        $nurse->branches()->attach($this->branch->id, ['access_level' => 'staff', 'company_id' => $this->company->id]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $nurse->assignRole($role);

        return $nurse;
    }

    protected function makeMar(NursingEpisode $episode, bool $controlled = false): NursingMedicationAdministration
    {
        $generic = PharmacyGeneric::create(['company_id' => $this->company->id, 'code' => 'G-'.uniqid(), 'generic_name' => 'Generic', 'is_active' => true]);
        $form = PharmacyDosageForm::create(['company_id' => $this->company->id, 'code' => 'F-'.uniqid(), 'name' => 'Tablet', 'is_active' => true]);
        $route = PharmacyRoute::create(['company_id' => $this->company->id, 'code' => 'R-'.uniqid(), 'name' => 'Oral', 'is_active' => true]);
        $medication = PharmacyMedication::create([
            'company_id' => $this->company->id, 'generic_id' => $generic->id, 'dosage_form_id' => $form->id,
            'route_id' => $route->id, 'code' => 'M-'.uniqid(), 'name' => 'Test Med', 'strength' => '500',
            'strength_unit' => 'mg', 'dispensing_unit' => 'tablet', 'is_prescription_required' => true,
            'is_controlled' => $controlled, 'is_active' => true,
        ]);

        return NursingMedicationAdministration::create([
            'company_id' => $episode->company_id, 'branch_id' => $episode->branch_id, 'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id, 'encounter_id' => $episode->encounter_id, 'patient_id' => $episode->patient_id,
            'medication_id' => $medication->id, 'scheduled_at' => now(), 'dose' => '500', 'dose_unit' => 'mg',
            'route' => 'oral', 'status' => NursingMedicationAdministration::STATUS_SCHEDULED,
        ]);
    }
}
