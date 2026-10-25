<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtComplex;
use App\Models\Ot\OtTheatre;
use App\Services\AuditLogger;
use App\Services\Ot\OtTheatreService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OtTheatreController extends Controller
{
    public function __construct(
        private readonly OtTheatreService $theatres,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index()
    {
        Gate::authorize('ot.theatre.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $theatres = OtTheatre::forTenant($companyId, $branchId)->with(['complex', 'capabilities'])->get();
        $complexes = OtComplex::forTenant($companyId, $branchId)->get();

        return view('admin.ot.theatres.index', compact('theatres', 'complexes'));
    }

    public function create()
    {
        Gate::authorize('ot.theatre.manage');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $complexes = OtComplex::forTenant($companyId)->get();

        return view('admin.ot.theatres.create', compact('complexes'));
    }

    public function store(Request $request)
    {
        Gate::authorize('ot.theatre.manage');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'complex_id' => ['nullable', 'integer', 'exists:ot_complexes,id'],
            'theatre_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'emergency_capable' => ['nullable', 'boolean'],
            'capabilities' => ['nullable', 'array'],
        ]);

        $theatre = $this->theatres->create($validated);

        $this->auditLogger->log('CREATE', OtTheatre::class, $theatre->id, null, $theatre->toArray(), $request);

        return redirect()->route('admin.ot.theatres.index')->with('success', 'Theatre created.');
    }

    public function update(Request $request, OtTheatre $theatre)
    {
        Gate::authorize('ot.theatre.manage');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'emergency_capable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->theatres->update($theatre, $validated);

        $this->auditLogger->log('UPDATE', OtTheatre::class, $theatre->id, null, $validated, $request);

        return back()->with('success', 'Theatre updated.');
    }

    public function storeComplex(Request $request)
    {
        Gate::authorize('ot.theatre.manage');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $this->theatres->createComplex($validated);

        return back()->with('success', 'OT complex created.');
    }
}
