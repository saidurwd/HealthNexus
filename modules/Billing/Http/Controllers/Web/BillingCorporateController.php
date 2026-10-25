<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCorporate;
use App\Models\Billing\BillingCorporateContract;
use App\Models\Billing\BillingCorporateMember;
use App\Models\Branch;
use App\Models\Organization;
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

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $corporates = BillingCorporate::query()->forTenant($organizationId, $branchId)->orderBy('name')->paginate(20);

        return view('admin.billing.corporates.index', compact('corporates'));
    }

    public function create()
    {
        $this->authorize('create', BillingCorporate::class);

        $organizations = Organization::all();
        $branches = Branch::all();

        return view('admin.billing.corporates.create', compact('organizations', 'branches'));
    }

    public function store(StoreBillingCorporateRequest $request)
    {
        $this->authorize('create', BillingCorporate::class);

        $corporate = BillingCorporate::create($request->validated());

        $this->auditLogger->log('CREATE', BillingCorporate::class, $corporate->id, null, $corporate->toArray(), $request);

        return redirect()->route('admin.billing.corporates.index')->with('success', __('Corporate created successfully.'));
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

        return redirect()->route('admin.billing.corporates.index')->with('success', __('Corporate updated successfully.'));
    }

    public function storeContract(StoreBillingCorporateContractRequest $request, BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        $contract = $corporate->contracts()->create([
            ...$request->validated(),
            'organization_id' => $corporate->organization_id,
            'branch_id' => $corporate->branch_id,
        ]);

        $this->auditLogger->log('CREATE', BillingCorporateContract::class, $contract->id, null, $contract->toArray(), $request);

        return redirect()->route('admin.billing.corporates.show', $corporate)->with('success', __('Contract added successfully.'));
    }

    public function storeMember(StoreBillingCorporateMemberRequest $request, BillingCorporate $corporate)
    {
        $this->authorize('update', $corporate);

        $member = $corporate->members()->create([
            ...$request->validated(),
            'organization_id' => $corporate->organization_id,
            'branch_id' => $corporate->branch_id,
        ]);

        $this->auditLogger->log('CREATE', BillingCorporateMember::class, $member->id, null, $member->toArray(), $request);

        return redirect()->route('admin.billing.corporates.show', $corporate)->with('success', __('Member added successfully.'));
    }

    public function receivables(Request $request)
    {
        $this->authorize('viewAny', BillingCorporate::class);

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $receivables = $this->revenue->outstandingByCorporate($organizationId, $branchId);

        return view('admin.billing.corporates.receivables', compact('receivables'));
    }
}
