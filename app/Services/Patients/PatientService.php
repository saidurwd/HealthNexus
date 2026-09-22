<?php

namespace App\Services\Patients;

use App\Events\PatientRegistered;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientAlert;
use App\Models\PatientAllergy;
use App\Models\PatientBranchRegistration;
use App\Models\PatientContact;
use App\Models\PatientHistory;
use App\Models\User;
use Illuminate\Support\Collection;
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

            if (! empty($data['identifiers'])) {
                foreach ($data['identifiers'] as $identifierData) {
                    $identifierData['company_id'] = $company->id;
                    $patient->identifiers()->create($identifierData);
                }
            }

            if (! empty($data['contacts'])) {
                foreach ($data['contacts'] as $contactData) {
                    $contactData['company_id'] = $company->id;
                    $patient->contacts()->create($contactData);
                }
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

    public function addContact(Patient $patient, array $contactData): PatientContact
    {
        $contactData['company_id'] = $patient->company_id;

        return $patient->contacts()->create($contactData);
    }

    public function addAllergy(Patient $patient, array $allergyData): PatientAllergy
    {
        $allergyData['company_id'] = $patient->company_id;

        return $patient->allergies()->create($allergyData);
    }

    public function addAlert(Patient $patient, array $alertData): PatientAlert
    {
        $alertData['company_id'] = $patient->company_id;
        $alertData['created_by'] = $alertData['created_by'] ?? auth()->id();

        return $patient->alerts()->create($alertData);
    }

    public function resolveAlert(PatientAlert $alert, array $data): PatientAlert
    {
        $alert->update(array_merge($data, [
            'status' => 'resolved',
            'resolved_by' => $data['resolved_by'] ?? auth()->id(),
            'resolved_at' => now(),
        ]));

        return $alert;
    }

    public function addHistory(Patient $patient, array $historyData): PatientHistory
    {
        $historyData['company_id'] = $patient->company_id;
        $historyData['recorded_by'] = auth()->id();

        return $patient->histories()->create($historyData);
    }

    public function updatePatient(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient;
    }

    public function detectDuplicates(int $companyId, string $firstName, string $lastName, ?string $phone = null, ?string $nationalId = null, ?string $email = null): Collection
    {
        return Patient::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($firstName, $lastName, $phone, $nationalId, $email) {
                $query->where('first_name', 'like', '%'.$firstName.'%')
                    ->where('last_name', 'like', '%'.$lastName.'%');

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($nationalId) {
                    $query->orWhere('national_identifier', $nationalId);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->get();
    }

    public function mergePatients(Patient $masterPatient, Patient $duplicatePatient): void
    {
        DB::transaction(function () use ($masterPatient, $duplicatePatient) {
            $this->transferRelations($masterPatient, $duplicatePatient);

            $duplicatePatient->update([
                'first_name' => $duplicatePatient->first_name.' (merged)',
                'status' => 'inactive',
            ]);

            $duplicatePatient->delete();
        });
    }

    protected function transferRelations(Patient $masterPatient, Patient $duplicatePatient): void
    {
        $duplicatePatient->identifiers()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->contacts()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->allergies()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->histories()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->alerts()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->documents()->update(['patient_id' => $masterPatient->id]);
        $duplicatePatient->branchRegistrations()->update(['patient_id' => $masterPatient->id]);
    }

    public function getTimeline(Patient $patient): Collection
    {
        $timeline = collect();

        $timeline->push($patient);

        $timeline = $timeline->merge($patient->identifiers);
        $timeline = $timeline->merge($patient->contacts);
        $timeline = $timeline->merge($patient->allergies);
        $timeline = $timeline->merge($patient->histories);
        $timeline = $timeline->merge($patient->documents);
        $timeline = $timeline->merge($patient->branchRegistrations);

        if ($patient->encounters()->exists()) {
            $timeline = $timeline->merge($patient->encounters);
        }

        return $timeline->sortBy('created_at')->reverse();
    }
}
