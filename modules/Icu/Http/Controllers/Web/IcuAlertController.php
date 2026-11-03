<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuCriticalAlert;
use App\Services\Icu\IcuAlertService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IcuAlertController extends Controller
{
    public function __construct(private readonly IcuAlertService $alerts) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IcuCriticalAlert::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $alerts = IcuCriticalAlert::forTenant($organizationId, $branchId)
            ->with('episode.patient')
            ->when($request->filled('unacknowledged'), fn ($q) => $q->whereNull('acknowledged_at'))
            ->latest('triggered_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.icu.alerts.index', compact('alerts'));
    }

    public function acknowledge(Request $request, IcuCriticalAlert $alert)
    {
        $this->authorize('acknowledge', $alert);

        try {
            $this->alerts->acknowledge($alert, $request->user(), $request->input('action'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.icu.alerts.index')->with('success', __('Alert acknowledged.'));
    }

    public function escalate(Request $request, IcuCriticalAlert $alert)
    {
        $this->authorize('manage', $alert);

        $this->alerts->escalate($alert, $request->user(), $request->input('escalated_to'));

        return redirect()->route('admin.icu.alerts.index')->with('success', __('Alert escalated.'));
    }
}
