<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabSection;
use App\Services\Laboratory\WorklistService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class LabWorklistController extends Controller
{
    public function __construct(private readonly WorklistService $worklist) {}

    public function index(Request $request)
    {
        $this->authorize('lab.result.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $items = $this->worklist->query($organizationId, $branchId, $request->only(['section_id', 'test_id', 'priority', 'status', 'date']));
        $sections = LabSection::query()->forTenant($organizationId, $branchId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.worklist.index', compact('items', 'sections'));
    }
}
