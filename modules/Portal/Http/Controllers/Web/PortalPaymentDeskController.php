<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalSettlement;
use App\Services\Portal\PortalPaymentService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Online payments and the match against the payment provider's settlement file (S06). */
class PortalPaymentDeskController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly PortalPaymentService $payments)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'portal.payment.view');
        $org = $this->organizationId();
        $status = $request->query('status');
        $rows = PortalPayment::query()->with(['patient:id,first_name,last_name,enterprise_patient_no', 'invoice:id,invoice_number'])->where('organization_id', $org)
            ->when($status === 'to_refund', fn ($q) => $q->where('status', 'succeeded')->whereNull('billing_payment_id'))->when($status === 'unreconciled', fn ($q) => $q->where('status', 'succeeded')->whereNull('reconciled_at')->whereNotNull('billing_payment_id'))
            ->when($status && ! in_array($status, ['to_refund', 'unreconciled'], true), fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString();

        return view('admin.portal.payments', ['rows' => $rows, 'status' => $status, 'settlements' => PortalSettlement::query()->where('organization_id', $org)->latest('id')->limit(10)->get(), 'enabled' => $this->payments->available()]);
    }

    public function sweep(Request $request)
    {
        $this->allow($request, 'portal.payment.reconcile');
        $r = $this->payments->sweep();

        return back()->with('success', __(':c confirmed, :e expired.', ['c' => $r['confirmed'], 'e' => $r['expired']]));
    }

    public function reconcile(Request $request)
    {
        $this->allow($request, 'portal.payment.reconcile');
        $d = $request->validate(['gateway' => ['required', 'string', 'max:20'], 'period_from' => ['required', 'date'], 'period_to' => ['required', 'date', 'after_or_equal:period_from'], 'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt']]);
        $lines = [];
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                if (! in_array('gateway_ref', $header, true) || ! in_array('amount', $header, true)) {
                    return back()->withErrors(['file' => __('The file needs the columns gateway_ref and amount (fee and paid_on are optional).')]);
                }

                continue;
            }
            if (count($row) === count($header)) {
                $lines[] = array_combine($header, $row);
            }
        }
        fclose($handle);
        $settlement = $this->payments->reconcile($this->organizationId(), $d['gateway'], $d['period_from'], $d['period_to'], $lines, $request->user(), $request->file('file')->getClientOriginalName());

        return redirect()->route('admin.portal.settlements.show', $settlement->public_id)->with('success', __(':m matched, :d need a look.', ['m' => $settlement->matched, 'd' => $settlement->differences]));
    }

    public function settlement(Request $request, string $settlement)
    {
        $this->allow($request, 'portal.payment.view');
        $row = $this->find(PortalSettlement::class, $settlement);

        return view('admin.portal.settlement', ['s' => $row, 'lines' => $row->lines()->orderByRaw("result = 'matched'")->orderBy('id')->get(), 'missing' => $this->payments->missingFromSettlement($this->organizationId(), $row->period_from->toDateString(), $row->period_to->toDateString())]);
    }
}
