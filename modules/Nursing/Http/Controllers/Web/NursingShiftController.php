<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingShift;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingShiftService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * No dedicated policy — gated directly via permission strings, consistent with how IPD's own
 * admission-request resource (also a simple lookup/sub-resource, not a full clinical record) is
 * authorized.
 */
class NursingShiftController extends Controller
{
    public function __construct(
        private readonly NursingShiftService $shifts,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index()
    {
        Gate::authorize('nursing.shift.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $shifts = NursingShift::forTenant($companyId, $branchId)->orderBy('start_time')->get();

        return view('admin.nursing.shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        Gate::authorize('nursing.shift.manage');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $shift = $this->shifts->create($validated);

        $this->auditLogger->log('CREATE', NursingShift::class, $shift->id, null, $shift->toArray(), $request);

        return back()->with('success', 'Shift created.');
    }

    public function update(Request $request, NursingShift $shift)
    {
        Gate::authorize('nursing.shift.manage');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'start_time' => ['sometimes'],
            'end_time' => ['sometimes'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->shifts->update($shift, $validated);

        $this->auditLogger->log('UPDATE', NursingShift::class, $shift->id, null, $validated, $request);

        return back()->with('success', 'Shift updated.');
    }
}
