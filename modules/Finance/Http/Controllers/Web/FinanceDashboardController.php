<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Posting;
use App\Services\Finance\FinanceDashboard;
use App\Services\Finance\PostingEngine;
use App\Services\Finance\ReconciliationService;
use App\Services\Finance\StockPostingService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The overview, the posting problems list and the Billing-vs-books check. */
class FinanceDashboardController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly FinanceDashboard $dashboard, private readonly PostingEngine $engine, private readonly ReconciliationService $reconciliation, private readonly StockPostingService $stock)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.dashboard.view');

        return view('admin.finance.dashboard', ['d' => $this->dashboard->summary($this->organizationId())]);
    }

    public function postings(Request $request)
    {
        $this->allow($request, 'finance.posting.view');
        $rows = Posting::query()->where('organization_id', $this->organizationId())->when($request->query('status', 'failed'), fn ($q, $v) => $v === 'all' ? $q : $q->where('status', $v))->latest('id')->paginate(30)->withQueryString();

        return view('admin.finance.postings', ['rows' => $rows, 'status' => $request->query('status', 'failed')]);
    }

    public function retry(Request $request)
    {
        $this->allow($request, 'finance.posting.retry');
        $org = $this->organizationId();
        $caught = array_sum($this->engine->catchUp($org));
        $stock = $this->stock->postReceipts($org) + $this->stock->postMovements($org)['journals'];
        $r = $this->engine->retryFailed($org);

        return back()->with('success', __('Looked for what was missed and tried the failed ones again: :n posted.', ['n' => $caught + $stock + $r['posted']]));
    }

    public function reconcile(Request $request)
    {
        $this->allow($request, 'finance.posting.view');
        [$from, $to] = $this->range($request);

        return view('admin.finance.reconcile', ['from' => $from, 'to' => $to, 'r' => $this->reconciliation->billingVsLedger($this->organizationId(), $from, $to)]);
    }
}
