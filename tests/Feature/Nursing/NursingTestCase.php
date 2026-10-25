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
use App\Services\Nursing\NursingEpisodeService;
use Spatie\Permission\Models\Role;
use Tests\Feature\Ipd\IpdTestCase;

abstract class NursingTestCase extends IpdTestCase
{
    protected function makeAdmission(?Patient $patient = null): IpdAdmission
    {
        $patient ??= Patient::factory()->create(['organization_id' => $this->organization->id]);
        $encounter = Encounter::factory()->create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);

        return IpdAdmission::create([
            'organization_id' => $this->organization->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
    }

    protected function makeEpisode(?IpdAdmission $admission = null): NursingEpisode
    {
        $admission ??= $this->makeAdmission();

        return app(NursingEpisodeService::class)->startFromAdmission($admission, $this->user);
    }

    protected function makeNurse(string $role = 'staff_nurse'): User
    {
        $nurse = User::factory()->create();
        $nurse->organizations()->attach($this->organization->id, ['access_level' => 'staff']);
        $nurse->branches()->attach($this->branch->id, ['access_level' => 'staff', 'organization_id' => $this->organization->id]);
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $nurse->assignRole($role);

        return $nurse;
    }

    protected function makeMar(NursingEpisode $episode, bool $controlled = false): NursingMedicationAdministration
    {
        $generic = PharmacyGeneric::create(['organization_id' => $this->organization->id, 'code' => 'G-'.uniqid(), 'generic_name' => 'Generic', 'is_active' => true]);
        $form = PharmacyDosageForm::create(['organization_id' => $this->organization->id, 'code' => 'F-'.uniqid(), 'name' => 'Tablet', 'is_active' => true]);
        $route = PharmacyRoute::create(['organization_id' => $this->organization->id, 'code' => 'R-'.uniqid(), 'name' => 'Oral', 'is_active' => true]);
        $medication = PharmacyMedication::create([
            'organization_id' => $this->organization->id, 'generic_id' => $generic->id, 'dosage_form_id' => $form->id,
            'route_id' => $route->id, 'code' => 'M-'.uniqid(), 'name' => 'Test Med', 'strength' => '500',
            'strength_unit' => 'mg', 'dispensing_unit' => 'tablet', 'is_prescription_required' => true,
            'is_controlled' => $controlled, 'is_active' => true,
        ]);

        return NursingMedicationAdministration::create([
            'organization_id' => $episode->organization_id, 'branch_id' => $episode->branch_id, 'episode_id' => $episode->id,
            'admission_id' => $episode->admission_id, 'encounter_id' => $episode->encounter_id, 'patient_id' => $episode->patient_id,
            'medication_id' => $medication->id, 'scheduled_at' => now(), 'dose' => '500', 'dose_unit' => 'mg',
            'route' => 'oral', 'status' => NursingMedicationAdministration::STATUS_SCHEDULED,
        ]);
    }
}
