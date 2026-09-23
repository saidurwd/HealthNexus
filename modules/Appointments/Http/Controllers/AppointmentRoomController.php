<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentRoom;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class AppointmentRoomController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('manageSchedule', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $rooms = AppointmentRoom::where('company_id', $companyId)->orderBy('name')->paginate(20);

        return view('admin.appointment-rooms.index', compact('rooms'));
    }

    public function create()
    {
        $this->authorize('manageSchedule', Appointment::class);

        return view('admin.appointment-rooms.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $validated = $this->validated($request);
        $validated['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $validated['branch_id'] = app(TenantContextResolver::class)->getBranchId();

        $room = AppointmentRoom::create($validated);

        $this->auditLogger->log('CREATE', AppointmentRoom::class, $room->id, null, $room->toArray(), $request);

        return redirect()->route('admin.appointment-rooms.index')->with('success', 'Room added successfully.');
    }

    public function edit(AppointmentRoom $appointmentRoom)
    {
        $this->authorize('manageSchedule', Appointment::class);

        return view('admin.appointment-rooms.edit', ['room' => $appointmentRoom]);
    }

    public function update(Request $request, AppointmentRoom $appointmentRoom)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $validated = $this->validated($request);
        $oldValues = $appointmentRoom->toArray();
        $appointmentRoom->update($validated);

        $this->auditLogger->log('UPDATE', AppointmentRoom::class, $appointmentRoom->id, $oldValues, $appointmentRoom->toArray(), $request);

        return redirect()->route('admin.appointment-rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(AppointmentRoom $appointmentRoom)
    {
        $this->authorize('manageSchedule', Appointment::class);

        $oldValues = $appointmentRoom->toArray();
        $appointmentRoom->delete();

        $this->auditLogger->log('DELETE', AppointmentRoom::class, $appointmentRoom->id, $oldValues, null, request());

        return redirect()->route('admin.appointment-rooms.index')->with('success', 'Room removed successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'room_type' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);
    }
}
