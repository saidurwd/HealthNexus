<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Provider;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function day(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $date = $request->input('date', today()->toDateString());

        $appointments = Appointment::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereDate('appointment_date', $date)
            ->with(['patient', 'doctor', 'provider', 'token'])
            ->orderBy('appointment_time')
            ->get();

        return view('admin.appointments.calendar-day', compact('appointments', 'date'));
    }

    public function provider(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $providerId = $request->input('provider_id');
        $date = $request->input('date', today()->toDateString());

        $providers = Provider::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get();

        $appointments = $providerId
            ? Appointment::where('provider_id', $providerId)->whereDate('appointment_date', $date)->with(['patient', 'token'])->orderBy('appointment_time')->get()
            : collect();

        return view('admin.appointments.calendar-provider', compact('providers', 'appointments', 'providerId', 'date'));
    }
}
