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

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $date = $request->input('date', today()->toDateString());

        $appointments = Appointment::query()
            ->when($organizationId, fn ($q, $id) => $q->where('organization_id', $id))
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

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $providerId = $request->input('provider_id');
        $date = $request->input('date', today()->toDateString());

        $providers = Provider::where('organization_id', $organizationId)->where('status', 'active')->orderBy('name')->get();

        $appointments = $providerId
            ? Appointment::where('provider_id', $providerId)->whereDate('appointment_date', $date)->with(['patient', 'token'])->orderBy('appointment_time')->get()
            : collect();

        return view('admin.appointments.calendar-provider', compact('providers', 'appointments', 'providerId', 'date'));
    }
}
