<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Receivable;
use App\Models\Finance\Writeoff;
use App\Services\Finance\ReceivableService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Who owes the hospital: ageing, a party's statement, write-offs with a second approver. */
class FinanceReceivableController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly ReceivableService $receivables)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.receivable.view');
        $type = in_array($request->query('type'), ['patient', 'corporate', 'payer'], true) ? $request->query('type') : null;
        $age = $this->receivables->ageing($this->organizationId(), $type, $request->query('on'));
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'receivable-ageing', array_merge(['Party type', 'Party'], $age['buckets'], ['Total']), $age['rows']->map(fn ($r) => array_merge([$r['party_type'], $r['name']], $r['buckets'], [$r['total']])));
        }

        return view('admin.finance.receivables', ['age' => $age, 'type' => $type]);
    }

    public function statement(Request $request, string $type, int $id)
    {
        $this->allow($request, 'finance.receivable.view');
        abort_unless(in_array($type, ['patient', 'corporate', 'payer'], true), 404);
        $items = $this->receivables->statement($this->organizationId(), $type, $id);
        abort_if($items->isEmpty(), 404);

        return view('admin.finance.receivable-statement', ['items' => $items, 'name' => $this->receivables->party($type, $id), 'type' => $type, 'svc' => $this->receivables]);
    }

    public function writeoffs(Request $request)
    {
        $this->allow($request, 'finance.receivable.view');

        return view('admin.finance.writeoffs', ['rows' => Writeoff::query()->where('organization_id', $this->organizationId())->with('receivable')->latest('id')->paginate(30), 'svc' => $this->receivables]);
    }

    public function requestWriteoff(Request $request, Receivable $receivable)
    {
        $this->allow($request, 'finance.receivable.writeoff');
        abort_unless($receivable->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['amount' => ['required', 'numeric'], 'reason' => ['required', 'string', 'max:250']]);
        $this->receivables->requestWriteoff($receivable, (string) $d['amount'], $d['reason'], $request->user());

        return redirect()->route('admin.finance.writeoffs')->with('success', __('Write-off requested; a second person must approve it.'));
    }

    public function decide(Request $request, Writeoff $writeoff)
    {
        $this->allow($request, 'finance.receivable.writeoff_approve');
        abort_unless($writeoff->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['decision' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:250']]);
        $this->receivables->decideWriteoff($writeoff, $d['decision'] === 'approve', $request->user(), $d['note'] ?? null);

        return back()->with('success', __('Done.'));
    }
}
