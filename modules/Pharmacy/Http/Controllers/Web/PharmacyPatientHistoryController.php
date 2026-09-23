<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyDispensingItem;
use App\Models\Pharmacy\PharmacyOrder;
use Illuminate\Support\Facades\Gate;

class PharmacyPatientHistoryController extends Controller
{
    public function show(Patient $patient)
    {
        Gate::authorize('pharmacy.prescription.view');

        $orders = PharmacyOrder::query()
            ->where('patient_id', $patient->id)
            ->with(['items.medication', 'items.safetyAlerts'])
            ->latest('ordered_at')
            ->get();

        $dispensedItems = PharmacyDispensingItem::query()
            ->whereHas('dispensing', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['medication', 'dispensing', 'batch'])
            ->latest('created_at')
            ->get();

        $allergies = $patient->allergies()->where('is_active', true)->get();

        return view('admin.pharmacy.patient-history.show', compact('patient', 'orders', 'dispensedItems', 'allergies'));
    }
}
