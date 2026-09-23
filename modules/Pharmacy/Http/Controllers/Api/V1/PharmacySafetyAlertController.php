<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
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
            ->when($request->has('is_overridden'), fn ($q) => $q->where('is_overridden', $request->boolean('is_overridden')))
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($alerts);
    }

    public function override(Request $request, PharmacySafetyAlert $alert)
    {
        $this->authorize('override', $alert);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $oldValues = $alert->toArray();

        $this->safety->override($alert, $validated['reason'], $request->user());

        $this->auditLogger->log('OVERRIDE', PharmacySafetyAlert::class, $alert->id, $oldValues, $alert->fresh()->toArray(), $request);

        return ApiResponse::success($alert->fresh(), 'Safety alert overridden.');
    }
}
