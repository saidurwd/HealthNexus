<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingCorporateContract;
use App\Models\Billing\BillingCorporateMember;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\Billing\RevenueService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\StoreBillingCorporateContractRequest;
use Modules\Billing\Http\Requests\Web\StoreBillingCorporateMemberRequest;
use Modules\Billing\Http\Requests\Web\StoreBillingCorporateRequest;
use Modules\Billing\Http\Requests\Web\UpdateBillingCorporateRequest;

class BillingCorporateController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RevenueService $revenue,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingCorporate::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $corporates = BillingCorporate::query()->forTenant($companyId, $branchId)->orderBy('name')->paginate(20);

        return view('admin.billing.corporates.index', compact('corporates'));
    }

    public function create()
    {
        $this->authorize('create', BillingCorporate::class);

        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.billing.corporates.create', compact('companies', 'branches'));
    }

    public function store(StoreBillingCorporateRequest $request)
    {
        $this->authorize('create', BillingCorporate::class);

        $corporate = BillingCorporate::create($request->validated());

        $this->auditLogger->log('CREATE', BillingCorporate::class, $corporate->id, null, $corporate->toArray(), $request);

        return redirect()->route('admin.billing.corporates.index')->with('success', 'Corporate created successfully.');
    }

    public function show(BillingCorporate $corporate)
    {
        $this->authorize('view', $corporate);

        $corporate->load(['contracts', 'members.patient']);

        return view('admin.billing.corporates.show', compact('corporate'));
    }

    public function edit(BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        return view('admin.billing.corporates.edit', compact('corporate'));
    }

    public function update(UpdateBillingCorporateRequest $request, BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        $oldValues = $corporate->toArray();

        $corporate->update($request->validated());

        $this->auditLogger->log('UPDATE', BillingCorporate::class, $corporate->id, $oldValues, $corporate->toArray(), $request);

        return redirect()->route('admin.billing.corporates.index')->with('success', 'Corporate updated successfully.');
    }

    public function storeContract(StoreBillingCorporateContractRequest $request, BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        $contract = $corporate->contracts()->create([
            ...$request->validated(),
            'company_id' => $corporate->company_id,
            'branch_id' => $corporate->branch_id,
        ]);

        $this->auditLogger->log('CREATE', BillingCorporateContract::class, $contract->id, null, $contract->toArray(), $request);

        return redirect()->route('admin.billing.corporates.show', $corporate)->with('success', 'Contract added successfully.');
    }

    public function storeMember(StoreBillingCorporateMemberRequest $request, BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        $member = $corporate->members()->create([
            ...$request->validated(),
            'company_id' => $corporate->company_id,
            'branch_id' => $corporate->branch_id,
        ]);

        $this->auditLogger->log('CREATE', BillingCorporateMember::class, $member->id, null, $member->toArray(), $request);

        return redirect()->route('admin.billing.corporates.show', $corporate)->with('success', 'Member added successfully.');
    }

    public function receivables(Request $request)
    {
        $this->authorize('viewAny', BillingCorporate::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $receivables = $this->revenue->outstandingByCorporate($companyId, $branchId);

        return view('admin.billing.corporates.receivables', compact('receivables'));
    }
}
