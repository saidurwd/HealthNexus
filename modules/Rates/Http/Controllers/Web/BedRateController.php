<?php

namespace Modules\Rates\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Rates\BedRateService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The bed and cabin rate card: read only. Prices are changed in Billing's price lists, not here. */
class BedRateController extends Controller
{
    public function __construct(private readonly BedRateService $rates, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('rates.bed.view'), 403);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $card = $this->rates->card($org, $branch);
        $estimate = null;
        if ($request->filled('bed_type')) {
            $v = $request->validate(['bed_type' => ['required', 'string', 'max:60'], 'days' => ['required', 'integer', 'between:1,365'], 'category' => ['nullable', 'string', 'max:60']]);
            $estimate = $this->rates->estimate($org, $branch, $v['bed_type'], (int) $v['days'], ($v['category'] ?? '') !== '' ? $v['category'] : null) ?? false;
        }

        return view('admin.rates.beds', ['card' => $card, 'estimate' => $estimate, 'asked' => $request->only(['bed_type', 'days', 'category']), 'labelled' => $this->rates->labelledRooms($org),
            'canBilling' => $request->user()->can('billing.pricing.view')]);
    }

    public function opd(Request $request)
    {
        abort_unless($request->user()->can('rates.opd.view'), 403);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;

        return view('admin.rates.opd', ['card' => $this->rates->opdCard($org, $branch), 'canBilling' => $request->user()->can('billing.pricing.view')]);
    }
}
