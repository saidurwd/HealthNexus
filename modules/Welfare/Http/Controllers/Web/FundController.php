<?php

namespace Modules\Welfare\Http\Controllers\Web;

use App\Models\Welfare\WfDonor;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use App\Services\TenantContextResolver;
use App\Services\Welfare\FundService;
use Illuminate\Http\Request;

class FundController extends WelfareController
{
    public function __construct(TenantContextResolver $tenant, private readonly FundService $funds)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'welfare.fund.view');
        $rows = WfFund::query()->where('organization_id', $this->organizationId())->orderBy('status')->orderBy('name')->get()->map(fn ($f) => ['fund' => $f, 'balance' => $this->funds->balance($f), 'committed' => $this->funds->committed($f), 'available' => $this->funds->available($f)]);

        return view('admin.welfare.funds', ['rows' => $rows, 'kinds' => config('welfare.fund_kinds'), 'canManage' => $request->user()->can('welfare.fund.manage')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'welfare.fund.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'kind' => ['required', 'string', 'max:12'], 'description' => ['nullable', 'string', 'max:300'], 'per_grant_cap' => ['nullable', 'numeric', 'min:0'], 'low_balance_alert' => ['nullable', 'numeric', 'min:0']]);
        $fund = $this->funds->createFund($this->organizationId(), $d, $request->user());

        return redirect()->route('admin.welfare.funds.show', $fund->public_id)->with('success', __('The fund was added.'));
    }

    public function show(Request $request, string $fund)
    {
        $this->allow($request, 'welfare.fund.view');
        $f = $this->find(WfFund::class, $fund);

        return view('admin.welfare.fund', ['fund' => $f, 'balance' => $this->funds->balance($f), 'committed' => $this->funds->committed($f), 'available' => $this->funds->available($f),
            'entries' => WfFundEntry::query()->where('fund_id', $f->id)->with('donor')->orderByDesc('occurred_at')->orderByDesc('id')->limit(100)->get(), 'donors' => WfDonor::query()->where('organization_id', $this->organizationId())->orderBy('name')->limit(300)->get(['id', 'name', 'anonymous']),
            'reversed' => WfFundEntry::query()->where('fund_id', $f->id)->where('entry_type', 'reversal')->pluck('reference')->map(fn ($r) => str_replace('reverses:', '', (string) $r))->all(),
            'canRecord' => $request->user()->can('welfare.donation.record'), 'canManage' => $request->user()->can('welfare.fund.manage')]);
    }

    public function donate(Request $request, string $fund)
    {
        $this->allow($request, 'welfare.donation.record');
        $f = $this->find(WfFund::class, $fund);
        $d = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'donor_id' => ['nullable', 'integer'], 'donor_name' => ['nullable', 'string', 'max:120'], 'donor_kind' => ['nullable', 'string', 'max:14'], 'donor_phone' => ['nullable', 'string', 'max:30'],
            'anonymous' => ['nullable', 'boolean'], 'reference' => ['nullable', 'string', 'max:80'], 'note' => ['nullable', 'string', 'max:300']]);
        $donor = null;
        if (! empty($d['donor_id'])) {
            $donor = WfDonor::query()->where('organization_id', $this->organizationId())->findOrFail($d['donor_id']);
        } elseif (! empty($d['donor_name'])) {
            $donor = $this->funds->donor($this->organizationId(), ['name' => $d['donor_name'], 'kind' => $d['donor_kind'] ?? 'individual', 'phone' => $d['donor_phone'] ?? null, 'anonymous' => $request->boolean('anonymous')]);
        }
        $entry = $this->funds->recordDonation($f, $d['amount'], $donor, $request->user(), $d['reference'] ?? null, $d['note'] ?? null);

        return back()->with('success', __('Received. Receipt :n.', ['n' => $entry->receipt_no]));
    }

    public function close(Request $request, string $fund)
    {
        $this->allow($request, 'welfare.fund.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->funds->close($this->find(WfFund::class, $fund), $request->user(), $d['reason']);

        return back()->with('success', __('The fund is closed.'));
    }

    public function reverse(Request $request, string $entry)
    {
        $this->allow($request, 'welfare.fund.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->funds->reverse($this->find(WfFundEntry::class, $entry), $request->user(), $d['reason']);

        return back()->with('success', __('The entry was reversed.'));
    }
}
