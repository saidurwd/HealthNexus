<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\Asset;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Period;
use App\Services\Finance\AssetService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The fixed-asset register, monthly depreciation and disposal. */
class FinanceAssetController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly AssetService $assets)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.asset.view');
        $org = $this->organizationId();
        $rows = Asset::query()->where('organization_id', $org)->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->orderBy('tag')->paginate(30)->withQueryString();
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'asset-register', ['Tag', 'Name', 'Acquired', 'Cost', 'Accumulated', 'Book value', 'Status'], Asset::query()->where('organization_id', $org)->orderBy('tag')->get()->map(fn ($a) => [$a->tag, $a->name, $a->acquired_on->toDateString(), $a->cost, $a->accumulated, $this->assets->bookValue($a), $a->status]));
        }

        return view('admin.finance.assets', ['rows' => $rows, 'svc' => $this->assets, 'centers' => CostCenter::query()->where(['organization_id' => $org, 'active' => true])->orderBy('code')->get(['id', 'code', 'name']),
            'periods' => Period::query()->where('organization_id', $org)->whereDate('starts_on', '<=', now()->toDateString())->orderByDesc('starts_on')->limit(12)->get(), 'accounts' => Account::query()->where(['organization_id' => $org, 'active' => true, 'postable' => true])->orderBy('code')->get(['code', 'name'])]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'finance.asset.manage');
        $d = $request->validate(['tag' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'], 'category' => ['nullable', 'string', 'max:30'], 'cost_center_id' => ['nullable', 'integer'], 'acquired_on' => ['required', 'date'], 'cost' => ['required', 'numeric'], 'salvage_value' => ['nullable', 'numeric'],
            'life_months' => ['nullable', 'integer'], 'credit_account' => ['nullable', 'string', 'max:20']]);
        $asset = $this->assets->register($this->organizationId(), $d, $request->user());
        ! empty($d['credit_account']) && $this->assets->postAcquisition($asset, $d['credit_account'], $request->user());

        return back()->with('success', __('Asset registered.'));
    }

    public function depreciate(Request $request)
    {
        $this->allow($request, 'finance.asset.manage');
        $period = Period::query()->where('organization_id', $this->organizationId())->findOrFail($request->validate(['period_id' => ['required', 'integer']])['period_id']);
        $r = $this->assets->depreciate($this->organizationId(), $period, $request->user());

        return back()->with('success', __(':n assets charged, :a in total.', ['n' => $r['assets'], 'a' => $r['amount']]));
    }

    public function dispose(Request $request, Asset $asset)
    {
        $this->allow($request, 'finance.asset.manage');
        abort_unless($asset->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['date' => ['required', 'date'], 'proceeds' => ['required', 'numeric'], 'account' => ['required', 'string', 'max:20']]);
        $this->assets->dispose($asset, $d['date'], (string) $d['proceeds'], $d['account'], $request->user());

        return back()->with('success', __('Asset disposed.'));
    }
}
