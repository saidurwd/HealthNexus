<?php

namespace Modules\Pharmacy\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyStock;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PharmacyStockController extends Controller
{
    public function stock(Request $request)
    {
        Gate::authorize('pharmacy.stock.view');

        $validated = $request->validate(['store_id' => ['required', 'integer', 'exists:pharmacy_stores,id']]);

        $stockRows = PharmacyStock::query()
            ->where('store_id', $validated['store_id'])
            ->where('quantity_available', '>', 0)
            ->with(['medication', 'batch'])
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($stockRows);
    }

    public function batches(Request $request)
    {
        Gate::authorize('pharmacy.batch.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $batches = PharmacyBatch::query()
            ->where('company_id', $companyId)
            ->with('medication')
            ->when($request->boolean('near_expiry'), fn ($q) => $q->where('expiry_date', '<=', now()->addDays(90)->toDateString()))
            ->orderBy('expiry_date')
            ->paginate((int) $request->input('per_page', 20));

        return ApiResponse::paginated($batches);
    }
}
