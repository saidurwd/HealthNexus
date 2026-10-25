<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Laboratory\LabAnalyzer;
use App\Models\Laboratory\LabSection;
use App\Models\Organization;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Foundation only — configures analyzer mapping records; no live ASTM/HL7 protocol.
 * See App\Contracts\Laboratory\LabAnalyzerAdapterInterface.
 */
class LabAnalyzerController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        Gate::authorize('lab.analyzer.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $analyzers = LabAnalyzer::query()->forTenant($organizationId, $branchId)->with('section')->orderBy('name')->paginate(20);

        return view('admin.lab.analyzers.index', compact('analyzers'));
    }

    public function create()
    {
        Gate::authorize('lab.analyzer.configure');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $sections = LabSection::query()->forTenant($organizationId)->where('is_active', true)->orderBy('name')->get();
        $organizations = Organization::all();
        $branches = Branch::all();

        return view('admin.lab.analyzers.create', compact('sections', 'organizations', 'branches'));
    }

    public function store(Request $request)
    {
        Gate::authorize('lab.analyzer.configure');

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'section_id' => ['nullable', 'integer', 'exists:lab_sections,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:100'],
            'connection_type' => ['required', 'in:file,astm,hl7,manual'],
        ]);

        $analyzer = LabAnalyzer::create([...$validated, 'is_active' => true]);

        $this->auditLogger->log('CREATE', LabAnalyzer::class, $analyzer->id, null, $analyzer->toArray(), $request);

        return redirect()->route('admin.lab.analyzers.index')->with('success', __('Analyzer registered.'));
    }
}
