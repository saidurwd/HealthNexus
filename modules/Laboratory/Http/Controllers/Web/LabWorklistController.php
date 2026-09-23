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

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $items = $this->worklist->query($companyId, $branchId, $request->only(['section_id', 'test_id', 'priority', 'status', 'date']));
        $sections = LabSection::query()->forTenant($companyId, $branchId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.worklist.index', compact('items', 'sections'));
    }
}
