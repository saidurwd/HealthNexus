<?php

namespace App\Services\Inventory;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Numbers for stores and procurement managers (S12). Everything reads the ledger, the stock projection and the read-only views. */
class InventoryReportService
{
    public function __construct(private readonly InventoryExpiryService $expiry, private readonly ReorderService $reorder, private readonly PurchaseOrderService $orders) {}

    /** @return array<string, mixed> */
    public function dashboard(int $organizationId): array
    {
        $value = DB::table('v_stock_valuation as v')->join('inventory_stores as s', 's.id', '=', 'v.store_id')->where('v.organization_id', $organizationId)->where('s.kind', '!=', 'quarantine')->sum('v.value');
        $po = DB::table('procurement_purchase_orders')->where('organization_id', $organizationId)->selectRaw('status, count(*) c, sum(total) t')->groupBy('status')->get()->keyBy('status');

        return [
            'stock_value' => number_format((float) $value, 2, '.', ''),
            'items' => DB::table('inventory_items')->where(['organization_id' => $organizationId, 'status' => 'active'])->count(),
            'below_reorder' => count($this->reorder->suggestions($organizationId)),
            'expiring_90' => $this->expiry->risk($organizationId, 90)->where('days_left', '>=', 0)->count(),
            'expired_in_stock' => $this->expiry->risk($organizationId, 0)->where('days_left', '<', 0)->count(),
            'quarantined_batches' => DB::table('inventory_batches')->where('organization_id', $organizationId)->whereIn('status', ['quarantined', 'recalled'])->count(),
            'open_orders' => (int) collect(['approved', 'issued', 'partially_received'])->sum(fn ($s) => (int) ($po[$s]->c ?? 0)),
            'orders_awaiting_approval' => (int) ($po['pending_approval']->c ?? 0),
            'requisitions_awaiting' => DB::table('procurement_requisitions')->where('organization_id', $organizationId)->where('status', 'submitted')->count(),
            'adjustments_pending' => DB::table('inventory_adjustments')->where('organization_id', $organizationId)->where('status', 'requested')->count(),
            'transfers_in_transit' => DB::table('inventory_transfers')->where('organization_id', $organizationId)->where('status', 'dispatched')->count(),
            'unratified_emergency' => $this->orders->overdueRatifications($organizationId)->count(),
            'invoices_to_review' => DB::table('procurement_supplier_invoices')->where('organization_id', $organizationId)->where('status', 'mismatch')->count(),
            'documents_expiring_30' => DB::table('procurement_supplier_documents')->where('organization_id', $organizationId)->whereBetween('expires_on', [now()->toDateString(), now()->addDays(30)->toDateString()])->count(),
        ];
    }

    /** Value of stock held per store. @return Collection<int, object> */
    public function valuation(int $organizationId): Collection
    {
        return DB::table('v_stock_valuation as v')->join('inventory_stores as s', 's.id', '=', 'v.store_id')->join('inventory_items as i', 'i.id', '=', 'v.item_id')->join('inventory_categories as c', 'c.id', '=', 'i.category_id')
            ->where('v.organization_id', $organizationId)->where('v.quantity_on_hand', '!=', 0)
            ->groupBy('s.id', 's.name', 'c.name')->selectRaw('s.name as store, c.name as category, SUM(v.quantity_on_hand) as quantity, SUM(v.value) as value')->orderBy('s.name')->orderBy('c.name')->get();
    }

    /** What each department used, by item (issues from stores plus point-of-care consumption). @return Collection<int, object> */
    public function consumptionByDepartment(int $organizationId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $issued = DB::table('inventory_issue_lines as l')->join('inventory_issues as i', 'i.id', '=', 'l.issue_id')->where('i.organization_id', $organizationId)->whereBetween('i.issued_at', [$from, $to])
            ->groupBy('i.department_id', 'l.item_id')->selectRaw('i.department_id, l.item_id, SUM(l.quantity - l.returned_quantity) as quantity, SUM((l.quantity - l.returned_quantity) * l.unit_cost) as cost');
        $consumed = DB::table('inventory_consumptions as c')->where('c.organization_id', $organizationId)->whereBetween('c.consumed_at', [$from, $to])
            ->groupBy('c.department_id', 'c.item_id')->selectRaw('c.department_id, c.item_id, SUM(c.quantity) as quantity, SUM(c.quantity * c.unit_cost) as cost');

        return DB::query()->fromSub($issued->unionAll($consumed), 'u')->leftJoin('departments as d', 'd.id', '=', 'u.department_id')->join('inventory_items as it', 'it.id', '=', 'u.item_id')
            ->groupBy('d.id', 'd.name', 'it.id', 'it.code', 'it.name')->selectRaw("COALESCE(d.name, 'No department') as department, it.code as item_code, it.name as item_name, SUM(u.quantity) as quantity, SUM(u.cost) as cost")
            ->orderBy('department')->orderByDesc('cost')->get();
    }

    /** Spend and delivery performance per supplier. @return Collection<int, object> */
    public function supplierPerformance(int $organizationId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::table('procurement_suppliers as s')->leftJoin('procurement_purchase_orders as o', fn ($j) => $j->on('o.supplier_id', '=', 's.id')->whereNotIn('o.status', ['draft', 'cancelled'])->whereBetween('o.order_date', [$from->toDateString(), $to->toDateString()]))
            ->where('s.organization_id', $organizationId)->groupBy('s.id', 's.name', 's.status', 's.rating')
            ->selectRaw('s.name, s.status, s.rating, COUNT(o.id) as orders, COALESCE(SUM(o.total), 0) as spend')->orderByDesc('spend')->get();
    }

    /** Accepted versus rejected quantity per supplier — the quality signal for the scorecard. @return Collection<int, object> */
    public function rejectionRate(int $organizationId): Collection
    {
        return DB::table('procurement_grn_lines as g')->join('procurement_goods_receipts as r', 'r.id', '=', 'g.goods_receipt_id')->join('procurement_suppliers as s', 's.id', '=', 'r.supplier_id')
            ->where('r.organization_id', $organizationId)->where('r.status', 'posted')->groupBy('s.id', 's.name')
            ->selectRaw('s.name, SUM(g.accepted_quantity) as accepted, SUM(g.rejected_quantity) as rejected')->get();
    }

    /** Items nobody has used for a while although stock is held. @return Collection<int, object> */
    public function slowMovers(int $organizationId, int $days = 90): Collection
    {
        return DB::table('inventory_stock as s')->join('inventory_items as i', 'i.id', '=', 's.item_id')->join('inventory_stores as st', 'st.id', '=', 's.store_id')
            ->where('s.organization_id', $organizationId)->where('st.kind', '!=', 'quarantine')->where('s.quantity_on_hand', '>', 0)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('stock_ledger as l')->whereColumn('l.item_id', 's.item_id')->where('l.store_kind', 'inventory')->where('l.txn_type', 'issue')->where('l.performed_at', '>=', now()->subDays($days)))
            ->groupBy('i.id', 'i.code', 'i.name')->selectRaw('i.code, i.name, SUM(s.quantity_on_hand) as quantity')->orderByDesc('quantity')->limit(50)->get();
    }
}
