<?php

namespace Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\LedgerService;
use App\Services\Finance\PayableService;
use App\Services\Finance\ReceivableService;
use App\Services\Finance\ReconciliationService;
use App\Services\Finance\StatementService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Read-only finance figures for dashboards and other systems: /api/v1/finance/*. The hospital is the one in the X-Organization-Id header, and only if the signed-in user belongs to it; a query parameter is never used. */
class FinanceReportApiController extends Controller
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly LedgerService $ledger, private readonly StatementService $statements, private readonly ReceivableService $receivables, private readonly PayableService $payables, private readonly ReconciliationService $reconciliation) {}

    private function org(Request $request, string $permission): int
    {
        abort_unless($request->user()->can($permission), 403);
        $org = (int) $this->tenant->getOrganizationId();
        abort_if($org === 0, 422, 'Say which hospital: send the X-Organization-Id header.');
        abort_unless($request->user()->organizations()->where('organizations.id', $org)->exists(), 403, 'You do not belong to that hospital.');

        return $org;
    }

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        $r = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $to = $r['to'] ?? now()->toDateString();

        return [$r['from'] ?? date('Y-m-01', strtotime($to)), $to];
    }

    private function ok(array $data, array $meta = []): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'OK', 'data' => $data, 'meta' => $meta]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.ledger.view');
        [$from, $to] = $this->range($request);
        $rows = $this->ledger->trialBalance($org, $from, $to);

        return $this->ok($rows->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'type' => $r->type, 'opening' => $r->opening, 'debit' => $r->debit, 'credit' => $r->credit, 'closing' => $r->closing])->all(), ['from' => $from, 'to' => $to] + $this->ledger->totals($rows));
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.report.view');
        [$from, $to] = $this->range($request);
        $r = $this->statements->incomeStatement($org, $from, $to);

        return $this->ok(['revenue' => $r['revenue']->values(), 'expense' => $r['expense']->values(), 'total_revenue' => $r['total_revenue'], 'total_expense' => $r['total_expense'], 'net' => $r['net']], ['from' => $from, 'to' => $to]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.report.view');
        $on = $request->validate(['on' => ['nullable', 'date']])['on'] ?? now()->toDateString();
        $r = $this->statements->balanceSheet($org, $on);

        return $this->ok(['assets' => $r['assets']->values(), 'liabilities' => $r['liabilities']->values(), 'equity' => $r['equity']->values(), 'current_result' => $r['current_result']] + array_intersect_key($r, array_flip(['total_assets', 'total_liabilities', 'total_equity', 'balanced'])), ['on' => $on]);
    }

    public function receivables(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.receivable.view');
        $a = $this->receivables->ageing($org);

        return $this->ok($a['rows']->map(fn ($r) => ['party_type' => $r['party_type'], 'name' => $r['name'], 'buckets' => $r['buckets'], 'total' => $r['total']])->all(), ['buckets' => $a['buckets'], 'totals' => $a['totals']]);
    }

    public function payables(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.payable.view');
        $a = $this->payables->ageing($org);

        return $this->ok(array_map(fn ($r) => ['name' => $r['name'], 'buckets' => $r['buckets'], 'total' => $r['total']], $a['rows']), ['buckets' => $a['buckets'], 'total' => $a['total']]);
    }

    public function reconciliation(Request $request): JsonResponse
    {
        $org = $this->org($request, 'finance.posting.view');
        [$from, $to] = $this->range($request);
        $r = $this->reconciliation->billingVsLedger($org, $from, $to);

        return $this->ok($r['rows'], ['unposted' => $r['unposted'], 'in_balance' => $r['in_balance'], 'from' => $from, 'to' => $to]);
    }
}
