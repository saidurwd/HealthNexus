<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\HospitalHoliday;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class HospitalHolidayController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index()
    {
        $this->authorize('manageHoliday', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $holidays = HospitalHoliday::where('company_id', $companyId)->orderBy('date')->paginate(20);

        return view('admin.holidays.index', compact('holidays'));
    }

    public function create()
    {
        $this->authorize('manageHoliday', Appointment::class);

        return view('admin.holidays.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manageHoliday', Appointment::class);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'is_recurring_annually' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = app(TenantContextResolver::class)->getCompanyId();

        $holiday = HospitalHoliday::create($validated);

        $this->auditLogger->log('CREATE', HospitalHoliday::class, $holiday->id, null, $holiday->toArray(), $request);

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday added successfully.');
    }

    public function destroy(HospitalHoliday $holiday)
    {
        $this->authorize('manageHoliday', Appointment::class);

        $oldValues = $holiday->toArray();
        $holiday->delete();

        $this->auditLogger->log('DELETE', HospitalHoliday::class, $holiday->id, $oldValues, null, request());

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday removed successfully.');
    }
}
