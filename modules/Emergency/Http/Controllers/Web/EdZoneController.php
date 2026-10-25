<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdBay;
use App\Models\Ed\EdZone;
use App\Services\Ed\EdBayService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdZoneController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly EdBayService $bays) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('ed.zone.manage'), 403);

        return view('admin.ed.zones', [
            'zones' => EdZone::query()->forTenant($this->tenant->getOrganizationId(), $this->tenant->getBranchId())->with('bays')->orderBy('sort_order')->get(),
            'types' => config('ed.zone_types'),
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('ed.zone.manage'), 403);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:100'],
            'zone_type' => ['required', 'in:'.implode(',', config('ed.zone_types'))], 'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        EdZone::create($validated + ['organization_id' => $this->tenant->getOrganizationId(), 'branch_id' => $this->tenant->getBranchId(), 'is_active' => true]);

        return back()->with('success', __('Zone created.'));
    }

    public function storeBay(Request $request, EdZone $zone): RedirectResponse
    {
        abort_unless($request->user()->can('ed.zone.manage') && $zone->organization_id === $this->tenant->getOrganizationId(), 403);
        $validated = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['nullable', 'string', 'max:100']]);

        $zone->bays()->create($validated + ['organization_id' => $zone->organization_id, 'status' => EdBay::AVAILABLE, 'is_active' => true]);

        return back()->with('success', __('Bay created.'));
    }

    public function bayStatus(Request $request, EdBay $bay): RedirectResponse
    {
        abort_unless($request->user()->can('ed.zone.manage') && $bay->organization_id === $this->tenant->getOrganizationId(), 403);
        $validated = $request->validate(['status' => ['required', 'in:available,cleaning,blocked']]);

        $this->bays->setStatus($bay, $validated['status']);

        return back()->with('success', __('Bay updated.'));
    }
}
