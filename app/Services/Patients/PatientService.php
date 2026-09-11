<?php

namespace App\Services\Patients;

use App\Events\PatientRegistered;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientBranchRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function __construct(private PatientNumberGenerator $numberGenerator) {}

    public function createPatient(array $data, User $user, ?Branch $branch = null): Patient
    {
        return DB::transaction(function () use ($data, $user, $branch): Patient {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['enterprise_patient_no'] = $this->numberGenerator->generateEnterprisePatientNo($company);

            $patient = Patient::create($data);

            if ($branch) {
                $this->registerPatientToBranch($patient, $branch, $user);
            }

            event(new PatientRegistered($patient));

            return $patient;
        });
    }

    public function registerPatientToBranch(Patient $patient, Branch $branch, User $user): PatientBranchRegistration
    {
        $company = $user->companies()->findOrFail($patient->company_id);

        $localPatientNo = $this->numberGenerator->generateLocalPatientNo($company, $branch);

        return PatientBranchRegistration::create([
            'company_id' => $company->id,
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'local_patient_no' => $localPatientNo,
            'registered_at' => now(),
            'status' => 'active',
        ]);
    }

    public function addIdentifier(Patient $patient, array $identifierData): void
    {
        $patient->identifiers()->create($identifierData);
    }

    public function addContact(Patient $patient, array $contactData): void
    {
        $patient->contacts()->create($contactData);
    }

    public function updatePatient(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient;
    }
}
