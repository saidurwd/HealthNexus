<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtProcedure;
use App\Models\Ot\OtProcedureCategory;
use App\Models\Specialty;
use App\Services\AuditLogger;
use App\Services\Ot\OtProcedureService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtProcedureController extends Controller
{
    public function __construct(
        private readonly OtProcedureService $procedures,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index()
    {
        Gate::authorize('ot.procedure.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $procedures = OtProcedure::forTenant($companyId, $branchId)->with(['category', 'specialty'])->orderBy('name')->get();
        $categories = OtProcedureCategory::forTenant($companyId, $branchId)->get();
        $specialties = Specialty::where('company_id', $companyId)->get();

        return view('admin.ot.procedures.index', compact('procedures', 'categories', 'specialties'));
    }

    public function create()
    {
        Gate::authorize('ot.procedure.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $categories = OtProcedureCategory::forTenant($companyId)->get();
        $specialties = Specialty::where('company_id', $companyId)->get();

        return view('admin.ot.procedures.create', compact('categories', 'specialties'));
    }

    public function store(Request $request)
    {
        Gate::authorize('ot.procedure.view');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:ot_procedure_categories,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'anesthesia_category' => ['nullable', 'string', 'max:255'],
            'laterality_applicable' => ['nullable', 'boolean'],
            'site_applicable' => ['nullable', 'boolean'],
            'day_care_eligible' => ['nullable', 'boolean'],
            'emergency_eligible' => ['nullable', 'boolean'],
            'requires_implant' => ['nullable', 'boolean'],
            'requires_blood' => ['nullable', 'boolean'],
            'requires_specimen' => ['nullable', 'boolean'],
        ]);

        $procedure = $this->procedures->create($validated);

        $this->auditLogger->log('CREATE', OtProcedure::class, $procedure->id, null, $procedure->toArray(), $request);

        return redirect()->route('admin.ot.procedures.index')->with('success', 'Procedure created.');
    }

    public function storeCategory(Request $request)
    {
        Gate::authorize('ot.procedure.view');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->procedures->createCategory($validated);

        return back()->with('success', 'Procedure category created.');
    }

    public function update(Request $request, OtProcedure $procedure)
    {
        Gate::authorize('ot.procedure.view');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->procedures->update($procedure, $validated);

        $this->auditLogger->log('UPDATE', OtProcedure::class, $procedure->id, null, $validated, $request);

        return back()->with('success', 'Procedure updated.');
    }
}
