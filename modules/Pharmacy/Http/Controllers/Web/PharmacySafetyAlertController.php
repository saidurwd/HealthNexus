<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyDispensingSafetyService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class PharmacySafetyAlertController extends Controller
{
    public function __construct(
        private readonly PharmacyDispensingSafetyService $safety,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacySafetyAlert::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $alerts = PharmacySafetyAlert::query()
            ->forTenant($companyId, $branchId)
            ->with(['medication', 'orderItem.order.patient'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $request->input('status') === 'overridden' ? $q->where('is_overridden', true) : $q->where('is_overridden', false);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.safety-alerts.index', compact('alerts'));
    }

    public function override(Request $request, PharmacySafetyAlert $alert)
    {
        $this->authorize('override', $alert);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $alert->toArray();

        $this->safety->override($alert, $validated['reason'], $request->user());

        $this->auditLogger->log('OVERRIDE', PharmacySafetyAlert::class, $alert->id, $oldValues, $alert->fresh()->toArray(), $request);

        return back()->with('success', 'Safety alert overridden.');
    }
}
