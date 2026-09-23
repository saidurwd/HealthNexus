<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class AppointmentTypeController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('manageSchedule', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $types = AppointmentType::forCompany($companyId)->orderBy('sort_order')->paginate(20);

        return view('admin.appointment-types.index', compact('types'));
    }

    public function create()
    {
        $this->authorize('manageSchedule', Appointment::class);

        return view('admin.appointment-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $validated = $this->validated($request);
        $validated['company_id'] = app(TenantContextResolver::class)->getCompanyId();

        $type = AppointmentType::create($validated);

        $this->auditLogger->log('CREATE', AppointmentType::class, $type->id, null, $type->toArray(), $request);

        return redirect()->route('admin.appointment-types.index')->with('success', 'Appointment type added successfully.');
    }

    public function edit(AppointmentType $appointmentType)
    {
        $this->authorize('manageSchedule', Appointment::class);

        return view('admin.appointment-types.edit', ['type' => $appointmentType]);
    }

    public function update(Request $request, AppointmentType $appointmentType)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $validated = $this->validated($request);
        $oldValues = $appointmentType->toArray();
        $appointmentType->update($validated);

        $this->auditLogger->log('UPDATE', AppointmentType::class, $appointmentType->id, $oldValues, $appointmentType->toArray(), $request);

        return redirect()->route('admin.appointment-types.index')->with('success', 'Appointment type updated successfully.');
    }

    public function destroy(AppointmentType $appointmentType)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $oldValues = $appointmentType->toArray();
        $appointmentType->delete();

        $this->auditLogger->log('DELETE', AppointmentType::class, $appointmentType->id, $oldValues, null, request());

        return redirect()->route('admin.appointment-types.index')->with('success', 'Appointment type removed successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_follow_up_type' => ['boolean'],
            'is_telemedicine_type' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }
}
