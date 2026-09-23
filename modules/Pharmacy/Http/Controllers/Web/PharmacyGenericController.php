<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyGenericService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyGenericController extends Controller
{
    public function __construct(
        private readonly PharmacyGenericService $generics,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.generic.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $generics = PharmacyGeneric::query()
            ->forTenant($companyId, $branchId)
            ->when($request->filled('search'), fn ($q) => $q->where('generic_name', 'like', '%'.$request->input('search').'%'))
            ->orderBy('generic_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.generics.index', compact('generics'));
    }

    public function create()
    {
        Gate::authorize('pharmacy.generic.create');

        return view('admin.pharmacy.generics.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('pharmacy.generic.create');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50'],
            'generic_name' => ['required', 'string', 'max:255'],
            'chemical_name' => ['nullable', 'string', 'max:255'],
            'therapeutic_class' => ['nullable', 'string', 'max:255'],
            'pharmacological_class' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $generic = $this->generics->create($validated, $request->user());

        $this->auditLogger->log('CREATE', PharmacyGeneric::class, $generic->id, null, $generic->toArray(), $request);

        return redirect()->route('admin.pharmacy.generics.index')->with('success', 'Generic created.');
    }

    public function edit(PharmacyGeneric $generic)
    {
        Gate::authorize('pharmacy.generic.update');

        return view('admin.pharmacy.generics.edit', compact('generic'));
    }

    public function update(Request $request, PharmacyGeneric $generic)
    {
        Gate::authorize('pharmacy.generic.update');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'generic_name' => ['required', 'string', 'max:255'],
            'chemical_name' => ['nullable', 'string', 'max:255'],
            'therapeutic_class' => ['nullable', 'string', 'max:255'],
            'pharmacological_class' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $generic->toArray();
        $this->generics->update($generic, $validated, $request->user());

        $this->auditLogger->log('UPDATE', PharmacyGeneric::class, $generic->id, $oldValues, $generic->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.generics.index')->with('success', 'Generic updated.');
    }
}
