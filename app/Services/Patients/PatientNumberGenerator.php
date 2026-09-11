<?php

namespace App\Services\Patients;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientBranchRegistration;

class PatientNumberGenerator
{
    public function generateEnterprisePatientNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $lastPatient = Patient::where('company_id', $company->id)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastPatient ? $lastPatient->id + 1 : 1;

        return sprintf('%s-%08d', $prefix, $sequence);
    }

    public function generateLocalPatientNo(Company $company, Branch $branch): string
    {
        $prefix = strtoupper(substr($branch->code, 0, 3));
        $lastRegistration = PatientBranchRegistration::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastRegistration ? $lastRegistration->id + 1 : 1;

        return sprintf('%s-%08d', $prefix, $sequence);
    }
}
