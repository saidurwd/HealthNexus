<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Radiology\RadiologyPacsServer;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class RadiologyPacsController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyPacsServer::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $servers = RadiologyPacsServer::query()->forTenant($organizationId, $branchId)->orderBy('name')->get();

        return view('admin.radiology.pacs.index', compact('servers'));
    }

    public function create()
    {
        $this->authorize('manage', RadiologyPacsServer::class);

        $organizations = Organization::all();
        $branches = Branch::all();

        return view('admin.radiology.pacs.create', compact('organizations', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage', RadiologyPacsServer::class);

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'adapter_type' => ['required', 'in:null,orthanc'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'ae_title' => ['nullable', 'string', 'max:100'],
            'port' => ['nullable', 'integer'],
            'username' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:100'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            RadiologyPacsServer::query()->where('organization_id', $validated['organization_id'])->update(['is_default' => false]);
        }

        $server = RadiologyPacsServer::create([...$validated, 'is_active' => true]);

        $this->auditLogger->log('CREATE', RadiologyPacsServer::class, $server->id, null, ['code' => $server->code, 'adapter_type' => $server->adapter_type], $request);

        return redirect()->route('admin.radiology.pacs.index')->with('success', 'PACS server registered.');
    }
}
