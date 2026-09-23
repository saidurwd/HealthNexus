<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabQcMaterial;
use App\Models\Laboratory\LabQcRun;
use App\Models\Laboratory\LabTest;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/**
 * Foundation only — plain CRUD records for QC runs, no Levey-Jennings/Westgard statistical engine.
 */
class LabQcController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LabQcRun::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $runs = LabQcRun::query()
            ->forTenant($companyId, $branchId)
            ->with(['qcMaterial', 'test'])
            ->latest('run_at')
            ->paginate(20);

        return view('admin.lab.qc.index', compact('runs'));
    }

    public function create()
    {
        $this->authorize('create', LabQcRun::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $materials = LabQcMaterial::query()->forTenant($companyId, $branchId)->where('is_active', true)->get();
        $tests = LabTest::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.lab.qc.create', compact('materials', 'tests'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', LabQcRun::class);

        $validated = $request->validate([
            'qc_material_id' => ['required', 'integer', 'exists:lab_qc_materials,id'],
            'test_id' => ['required', 'integer', 'exists:lab_tests,id'],
            'expected_low' => ['nullable', 'numeric'],
            'expected_high' => ['nullable', 'numeric'],
            'observed_value' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $status = 'pending';

        if ($validated['expected_low'] !== null || $validated['expected_high'] !== null) {
            $inRange = (! isset($validated['expected_low']) || $validated['observed_value'] >= $validated['expected_low'])
                && (! isset($validated['expected_high']) || $validated['observed_value'] <= $validated['expected_high']);
            $status = $inRange ? 'pass' : 'fail';
        }

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $run = LabQcRun::create([
            ...$validated,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'run_at' => now(),
            'status' => $status,
            'performed_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('CREATE', LabQcRun::class, $run->id, null, $run->toArray(), $request);

        return redirect()->route('admin.lab.qc.index')->with('success', 'QC run recorded ('.$status.').');
    }
}
