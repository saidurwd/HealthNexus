<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Laboratory\LabPanel;
use App\Models\Laboratory\LabTest;
use App\Services\AuditLogger;
use App\Services\Laboratory\LabPanelService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Laboratory\Http\Requests\Web\StoreLabPanelRequest;
use Modules\Laboratory\Http\Requests\Web\UpdateLabPanelRequest;

class LabPanelController extends Controller
{
    public function __construct(
        private readonly LabPanelService $panels,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabTest::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $panels = LabPanel::query()
            ->forTenant($organizationId, $branchId)
            ->withCount('items')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.lab.panels.index', compact('panels'));
    }

    public function create()
    {
        $this->authorize('create', LabTest::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $tests = LabTest::query()->forTenant($organizationId)->where('is_active', true)->orderBy('name')->get();
        $organizations = Organization::all();
        $branches = Branch::all();

        return view('admin.lab.panels.create', compact('tests', 'organizations', 'branches'));
    }

    public function store(StoreLabPanelRequest $request)
    {
        $this->authorize('create', LabTest::class);

        $data = $request->safe()->except('test_ids');
        $panel = $this->panels->create($data, $request->validated('test_ids'), $request->user());

        $this->auditLogger->log('CREATE', LabPanel::class, $panel->id, null, $panel->toArray(), $request);

        return redirect()->route('admin.lab.panels.index')->with('success', __('Lab panel created.'));
    }

    public function edit(LabPanel $panel)
    {
        $this->authorize('update', LabTest::class);

        $panel->load('items');
        $tests = LabTest::query()->forTenant($panel->organization_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.panels.edit', compact('panel', 'tests'));
    }

    public function update(UpdateLabPanelRequest $request, LabPanel $panel)
    {
        $this->authorize('update', LabTest::class);

        $oldValues = $panel->toArray();
        $data = $request->safe()->except('test_ids');
        $this->panels->update($panel, $data, $request->validated('test_ids'), $request->user());

        $this->auditLogger->log('UPDATE', LabPanel::class, $panel->id, $oldValues, $panel->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.panels.index')->with('success', __('Lab panel updated.'));
    }
}
