<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingInsurancePolicy;
use App\Models\Billing\BillingInsuranceProvider;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Billing\Http\Requests\Web\StoreInsurancePolicyRequest;
use Modules\Billing\Http\Requests\Web\StoreInsuranceProviderRequest;
use Modules\Billing\Http\Requests\Web\UpdateInsurancePolicyRequest;
use Modules\Billing\Http\Requests\Web\UpdateInsuranceProviderRequest;

class BillingInsuranceController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function providers(Request $request)
    {
        Gate::authorize('insurance.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $providers = BillingInsuranceProvider::query()->forTenant($companyId)->orderBy('name')->paginate(20);

        return view('admin.billing.insurance.providers.index', compact('providers'));
    }

    public function createProvider()
    {
        Gate::authorize('insurance.create');

        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.insurance.providers.create', compact('companies', 'branches'));
    }

    public function storeProvider(StoreInsuranceProviderRequest $request)
    {
        Gate::authorize('insurance.create');

        $provider = BillingInsuranceProvider::create($request->validated());

        $this->auditLogger->log('CREATE', BillingInsuranceProvider::class, $provider->id, null, $provider->toArray(), $request);

        return redirect()->route('admin.billing.insurance.providers.index')->with('success', 'Insurance provider created successfully.');
    }

    public function editProvider(BillingInsuranceProvider $provider)
    {
        Gate::authorize('insurance.update');

        return view('admin.billing.insurance.providers.edit', compact('provider'));
    }

    public function updateProvider(UpdateInsuranceProviderRequest $request, BillingInsuranceProvider $provider)
    {
        Gate::authorize('insurance.update');

        $oldValues = $provider->toArray();

        $provider->update($request->validated());

        $this->auditLogger->log('UPDATE', BillingInsuranceProvider::class, $provider->id, $oldValues, $provider->toArray(), $request);

        return redirect()->route('admin.billing.insurance.providers.index')->with('success', 'Insurance provider updated successfully.');
    }

    public function policies(Request $request)
    {
        $this->authorize('viewAny', BillingInsurancePolicy::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $policies = BillingInsurancePolicy::query()
            ->forTenant($companyId)
            ->with(['provider', 'patient'])
            ->latest()
            ->paginate(20);

        return view('admin.billing.insurance.policies.index', compact('policies'));
    }

    public function createPolicy()
    {
        $this->authorize('create', BillingInsurancePolicy::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $providers = BillingInsuranceProvider::query()->forTenant($companyId)->where('status', 'active')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.insurance.policies.create', compact('providers', 'companies', 'branches'));
    }

    public function storePolicy(StoreInsurancePolicyRequest $request)
    {
        $this->authorize('create', BillingInsurancePolicy::class);

        $policy = BillingInsurancePolicy::create($request->validated());

        $this->auditLogger->log('CREATE', BillingInsurancePolicy::class, $policy->id, null, $policy->toArray(), $request);

        return redirect()->route('admin.billing.insurance.policies.index')->with('success', 'Insurance policy created successfully.');
    }

    public function editPolicy(BillingInsurancePolicy $policy)
    {
        $this->authorize('update', $policy);

        $providers = BillingInsuranceProvider::query()->forTenant($policy->company_id)->where('status', 'active')->get();

        return view('admin.billing.insurance.policies.edit', compact('policy', 'providers'));
    }

    public function updatePolicy(UpdateInsurancePolicyRequest $request, BillingInsurancePolicy $policy)
    {
        $this->authorize('update', $policy);

        $oldValues = $policy->toArray();

        $policy->update($request->validated());

        $this->auditLogger->log('UPDATE', BillingInsurancePolicy::class, $policy->id, $oldValues, $policy->toArray(), $request);

        return redirect()->route('admin.billing.insurance.policies.index')->with('success', 'Insurance policy updated successfully.');
    }
}
