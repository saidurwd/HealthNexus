<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use Illuminate\Support\Facades\Gate;

class IpdPatientHistoryController extends Controller
{
    public function show(Patient $patient)
    {
        Gate::authorize('ipd.admission.view');

        $admissions = IpdAdmission::query()
            ->where('patient_id', $patient->id)
            ->with(['admissionType', 'currentAllocation.bed.room.ward', 'attendingProvider', 'dischargeDisposition'])
            ->latest('admitted_at')
            ->get();

        return view('admin.ipd.patient-history.show', compact('patient', 'admissions'));
    }
}
