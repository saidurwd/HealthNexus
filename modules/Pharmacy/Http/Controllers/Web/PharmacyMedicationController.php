<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Pharmacy\PharmacyBrand;
use App\Models\Pharmacy\PharmacyDosageForm;
use App\Models\Pharmacy\PharmacyGeneric;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyRoute;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyMedicationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Pharmacy\Http\Requests\Web\StorePharmacyMedicationRequest;
use Modules\Pharmacy\Http\Requests\Web\UpdatePharmacyMedicationRequest;

class PharmacyMedicationController extends Controller
{
    public function __construct(
        private readonly PharmacyMedicationService $medications,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PharmacyMedication::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $medications = PharmacyMedication::query()
            ->forTenant($companyId, $branchId)
            ->with(['generic', 'brand', 'dosageForm'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.medications.index', compact('medications'));
    }

    public function create()
    {
        $this->authorize('create', PharmacyMedication::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $generics = PharmacyGeneric::query()->forTenant($companyId)->where('is_active', true)->orderBy('generic_name')->get();
        $brands = PharmacyBrand::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $dosageForms = PharmacyDosageForm::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $routes = PharmacyRoute::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.pharmacy.medications.create', compact('generics', 'brands', 'dosageForms', 'routes', 'companies', 'branches'));
    }

    public function store(StorePharmacyMedicationRequest $request)
    {
        $this->authorize('create', PharmacyMedication::class);

        $medication = $this->medications->create($request->validated(), $request->user());

        $this->auditLogger->log('CREATE', PharmacyMedication::class, $medication->id, null, $medication->toArray(), $request);

        return redirect()->route('admin.pharmacy.medications.index')->with('success', 'Medication created.');
    }

    public function edit(PharmacyMedication $medication)
    {
        $this->authorize('update', $medication);

        $generics = PharmacyGeneric::query()->forTenant($medication->company_id)->where('is_active', true)->orderBy('generic_name')->get();
        $brands = PharmacyBrand::query()->forTenant($medication->company_id)->where('is_active', true)->orderBy('name')->get();
        $dosageForms = PharmacyDosageForm::query()->forTenant($medication->company_id)->where('is_active', true)->orderBy('name')->get();
        $routes = PharmacyRoute::query()->forTenant($medication->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.pharmacy.medications.edit', compact('medication', 'generics', 'brands', 'dosageForms', 'routes'));
    }

    public function update(UpdatePharmacyMedicationRequest $request, PharmacyMedication $medication)
    {
        $this->authorize('update', $medication);

        $oldValues = $medication->toArray();
        $this->medications->update($medication, $request->validated(), $request->user());

        $this->auditLogger->log('UPDATE', PharmacyMedication::class, $medication->id, $oldValues, $medication->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.medications.index')->with('success', 'Medication updated.');
    }

    public function destroy(Request $request, PharmacyMedication $medication)
    {
        $this->authorize('delete', $medication);

        $oldValues = $medication->toArray();
        $this->medications->deactivate($medication, $request->user());

        $this->auditLogger->log('DEACTIVATE', PharmacyMedication::class, $medication->id, $oldValues, null, $request);

        return redirect()->route('admin.pharmacy.medications.index')->with('success', 'Medication deactivated.');
    }
}
