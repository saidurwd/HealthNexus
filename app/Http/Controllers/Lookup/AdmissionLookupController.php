<?php

namespace App\Http\Controllers\Lookup;

use App\Models\Ipd\IpdAdmission;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Finds inpatient admissions for a searchable dropdown: the chosen patient's own admissions, newest first, or by admission number or patient when no patient is chosen. Twenty at a time. */
class AdmissionLookupController extends LookupController
{
    public function __invoke(Request $request): JsonResponse
    {
        $org = $this->organizationId($request);
        $query = IpdAdmission::query()->where('ipd_admissions.organization_id', $org)->join('patients', 'patients.id', '=', 'ipd_admissions.patient_id')->where('patients.organization_id', $org);
        $patientId = (int) $request->query('patient_id', 0);
        $patientId > 0 && $query->where('ipd_admissions.patient_id', $patientId);
        foreach ($this->words($request) as $word) {
            $this->matching($query, ['ipd_admissions.admission_number', 'patients.first_name', 'patients.last_name', 'patients.enterprise_patient_no'], $word);
        }
        $tz = (string) config('ux.timezone', 'Asia/Dhaka');

        return response()->json($query->orderByDesc('ipd_admissions.admitted_at')->limit(20)->get(['ipd_admissions.id', 'ipd_admissions.admission_number', 'ipd_admissions.status', 'ipd_admissions.admitted_at', 'patients.first_name', 'patients.last_name'])
            ->map(fn ($a) => ['value' => (string) $a->id, 'text' => $a->admission_number.' · '.str_replace('_', ' ', (string) $a->status).' · '.CarbonImmutable::parse($a->admitted_at, 'UTC')->setTimezone($tz)->format('d M Y').($patientId > 0 ? '' : ' · '.trim($a->getAttribute('first_name').' '.$a->getAttribute('last_name')))])->values());
    }
}
