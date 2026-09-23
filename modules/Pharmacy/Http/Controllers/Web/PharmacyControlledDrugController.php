<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyControlledDrugTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyControlledDrugController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('pharmacy.controlled_drug.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $stores = PharmacyStore::query()->forTenant($companyId, $branchId)->where('is_active', true)->orderBy('name')->get();
        $storeId = $request->integer('store_id') ?: $stores->first()?->id;

        $transactions = PharmacyControlledDrugTransaction::query()
            ->where('store_id', $storeId)
            ->with(['medication', 'batch', 'performedBy', 'witnessedBy'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.pharmacy.controlled-drugs.index', compact('stores', 'storeId', 'transactions'));
    }
}
