<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Laboratory\LabContainerType;
use App\Models\Laboratory\LabSection;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Laboratory\LabTestCategory;
use App\Services\AuditLogger;
use App\Services\Laboratory\LabTestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Laboratory\Http\Requests\Web\StoreLabTestRequest;
use Modules\Laboratory\Http\Requests\Web\UpdateLabTestRequest;

class LabTestController extends Controller
{
    public function __construct(
        private readonly LabTestService $tests,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabTest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $tests = LabTest::query()
            ->forTenant($companyId, $branchId)
            ->with(['category', 'section'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->input('section_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lab.tests.index', compact('tests'));
    }

    public function create()
    {
        $this->authorize('create', LabTest::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $categories = LabTestCategory::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $sections = LabSection::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $specimenTypes = LabSpecimenType::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $containerTypes = LabContainerType::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.lab.tests.create', compact('categories', 'sections', 'specimenTypes', 'containerTypes', 'companies', 'branches'));
    }

    public function store(StoreLabTestRequest $request)
    {
        $this->authorize('create', LabTest::class);

        $test = $this->tests->create($request->validated(), $request->user());

        $this->auditLogger->log('CREATE', LabTest::class, $test->id, null, $test->toArray(), $request);

        return redirect()->route('admin.lab.tests.index')->with('success', 'Lab test created.');
    }

    public function edit(LabTest $test)
    {
        $this->authorize('update', $test);

        $categories = LabTestCategory::query()->forTenant($test->company_id)->where('is_active', true)->orderBy('name')->get();
        $sections = LabSection::query()->forTenant($test->company_id)->where('is_active', true)->orderBy('name')->get();
        $specimenTypes = LabSpecimenType::query()->forTenant($test->company_id)->where('is_active', true)->orderBy('name')->get();
        $containerTypes = LabContainerType::query()->forTenant($test->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.tests.edit', compact('test', 'categories', 'sections', 'specimenTypes', 'containerTypes'));
    }

    public function update(UpdateLabTestRequest $request, LabTest $test)
    {
        $this->authorize('update', $test);

        $oldValues = $test->toArray();
        $this->tests->update($test, $request->validated(), $request->user());

        $this->auditLogger->log('UPDATE', LabTest::class, $test->id, $oldValues, $test->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.tests.index')->with('success', 'Lab test updated.');
    }

    public function destroy(Request $request, LabTest $test)
    {
        $this->authorize('delete', $test);

        $oldValues = $test->toArray();
        $this->tests->deactivate($test, $request->user());

        $this->auditLogger->log('DEACTIVATE', LabTest::class, $test->id, $oldValues, null, $request);

        return redirect()->route('admin.lab.tests.index')->with('success', 'Lab test deactivated.');
    }
}
