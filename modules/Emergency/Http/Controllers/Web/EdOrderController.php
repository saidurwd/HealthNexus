<?php

namespace Modules\Emergency\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ed\EdVisit;
use App\Services\Ed\EdOrderService;
use App\Services\TenantContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EdOrderController extends Controller
{
    public function __construct(private readonly EdOrderService $orders, private readonly TenantContextResolver $tenant) {}

    public function order(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.order.place');
        $validated = $request->validate([
            'order_type' => ['required', 'in:'.implode(',', EdOrderService::ORDER_TYPES)],
            'priority' => ['nullable', 'in:routine,urgent,stat'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:200'],
            'items.*.code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->placeOrder($visit, $request->user(), $validated['order_type'], $validated['items'], $validated['priority'] ?? null, $validated['notes'] ?? null);

        return back()->with('success', __('Order sent.'));
    }

    public function prescribe(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.order.place');
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['nullable', 'string', 'max:200'],
            'items.*.strength' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['required_with:items.*.medicine_name', 'string', 'max:100'],
            'items.*.duration' => ['required_with:items.*.medicine_name', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $this->orders->prescribe($visit, $request->user(), $validated['items']);

        return back()->with('success', __('Prescription sent to the pharmacy.'));
    }

    public function medication(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.medication.administer');
        $validated = $request->validate([
            'drug_name' => ['required', 'string', 'max:200'],
            'dose' => ['required', 'string', 'max:100'],
            'route' => ['required', 'string', 'max:50'],
            'is_verbal_order' => ['sometimes', 'boolean'],
            'ordered_by' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orders->giveMedication($visit, $request->user(), $validated);

        return back()->with('success', __('Dose recorded.'));
    }

    public function procedure(Request $request, EdVisit $visit): RedirectResponse
    {
        $this->authorizeVisit($request, $visit, 'ed.procedure.record');
        $validated = $request->validate(['procedure_name' => ['required', 'string', 'max:200'], 'notes' => ['nullable', 'string', 'max:1000']]);

        $this->orders->recordProcedure($visit, $request->user(), $validated['procedure_name'], $validated['notes'] ?? null);

        return back()->with('success', __('Procedure recorded.'));
    }

    private function authorizeVisit(Request $request, EdVisit $visit, string $permission): void
    {
        abort_unless($visit->organization_id === $this->tenant->getOrganizationId(), 404);
        abort_unless(! $this->tenant->getBranchId() || $visit->branch_id === $this->tenant->getBranchId(), 404);
        abort_unless($request->user()->can($permission), 403);
    }
}
