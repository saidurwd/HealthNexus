<?php

namespace Modules\Billing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Billing\BillingCashierSession;
use App\Models\Department;
use App\Services\AuditLogger;
use App\Services\Billing\CashierSessionService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\Web\CloseCashierSessionRequest;
use Modules\Billing\Http\Requests\Web\OpenCashierSessionRequest;

class BillingCashierSessionController extends Controller
{
    public function __construct(
        private readonly CashierSessionService $sessions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BillingCashierSession::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $sessions = BillingCashierSession::query()
            ->forTenant($companyId, $branchId)
            ->with('user')
            ->latest('opened_at')
            ->paginate(20);

        return view('admin.billing.cashier.index', compact('sessions'));
    }

    public function mySession(Request $request)
    {
        $session = BillingCashierSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        $reconciliation = $session ? $this->sessions->reconcile($session) : null;

        return view('admin.billing.cashier.my-session', compact('session', 'reconciliation'));
    }

    public function create()
    {
        $this->authorize('open', BillingCashierSession::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $counters = Department::where('company_id', $companyId)->orderBy('name')->get();

        return view('admin.billing.cashier.open', compact('counters'));
    }

    public function store(OpenCashierSessionRequest $request)
    {
        $this->authorize('open', BillingCashierSession::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $counter = $request->validated('counter_id') ? Department::find($request->validated('counter_id')) : null;

        $session = $this->sessions->open($request->user(), $companyId, $branchId, (string) $request->validated('opening_balance'), $counter);

        $this->auditLogger->log('CREATE', BillingCashierSession::class, $session->id, null, $session->toArray(), $request);

        return redirect()->route('admin.billing.cashier.my-session')->with('success', 'Cashier session opened.');
    }

    public function edit(BillingCashierSession $session)
    {
        $this->authorize('close', $session);

        $reconciliation = $this->sessions->reconcile($session);

        return view('admin.billing.cashier.close', compact('session', 'reconciliation'));
    }

    public function close(CloseCashierSessionRequest $request, BillingCashierSession $session)
    {
        $this->authorize('close', $session);

        $oldValues = $session->toArray();

        $this->sessions->close($session, (string) $request->validated('actual_closing'), $request->validated('notes'), $request->user());

        $this->auditLogger->log('CLOSE', BillingCashierSession::class, $session->id, $oldValues, $session->fresh()->toArray(), $request);

        return redirect()->route('admin.billing.cashier.my-session')->with('success', 'Cashier session closed.');
    }

    public function reconciliation(BillingCashierSession $session)
    {
        $this->authorize('reconcile', $session);

        $reconciliation = $this->sessions->reconcile($session);

        return view('admin.billing.cashier.reconciliation', compact('session', 'reconciliation'));
    }
}
