<?php

namespace Modules\Welfare\Http\Controllers\Web;

use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFundEntry;
use App\Services\Dashboard\DashboardCache;
use App\Services\TenantContextResolver;
use App\Services\Welfare\WelfareOverview;
use App\Support\Dashboard\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WelfareHomeController extends WelfareController
{
    public function __construct(TenantContextResolver $tenant, private readonly WelfareOverview $overview, private readonly DashboardCache $cache)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'welfare.dashboard.view');
        $org = $this->organizationId();
        $period = DashboardPeriod::fromRequest($request, '30d', ['7d', '30d', '90d', 'month', 'last_month', 'custom']);
        $r = $this->cache->remember($request->user(), 'welfare', $org, $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null, $period, fn () => $this->overview->summaryFor($org, $period), fresh: $request->boolean('fresh'));

        return view('admin.welfare.index', ['s' => $r['data'], 'asOf' => $r['as_of'], 'period' => $period, 'can' => ['assess' => $request->user()->can('welfare.assessment.view'), 'grant' => $request->user()->can('welfare.grant.view'), 'fund' => $request->user()->can('welfare.fund.view')],
            'waiting' => $request->user()->can('welfare.assessment.view') ? WfAssessment::query()->where('organization_id', $org)->where('status', 'submitted')->with('patient')->orderBy('updated_at')->limit(8)->get() : collect(),
            'billing' => $request->user()->can('welfare.grant.view') ? WfApplication::query()->where('organization_id', $org)->where('status', 'requested')->with('grant', 'invoice')->orderBy('requested_at')->limit(8)->get() : collect()]);
    }

    /** Totals only: no patient name, income or circumstance is on this page. */
    public function reports(Request $request)
    {
        $this->allow($request, 'welfare.report.view');
        $org = $this->organizationId();
        $from = today()->subMonths(5)->startOfMonth();
        $driver = DB::getDriverName();
        $month = $driver === 'sqlite' ? "strftime('%Y-%m', occurred_at)" : "date_format(occurred_at, '%Y-%m')";
        $in = WfFundEntry::query()->where('organization_id', $org)->where('entry_type', 'donation')->where('occurred_at', '>=', $from)->selectRaw("$month as m, sum(amount) as total")->groupBy('m')->pluck('total', 'm');
        $out = WfFundEntry::query()->where('organization_id', $org)->where('entry_type', 'disbursement')->where('occurred_at', '>=', $from)->selectRaw("$month as m, sum(amount) as total")->groupBy('m')->pluck('total', 'm');
        $months = collect(range(0, 5))->map(fn ($i) => $from->copy()->addMonths($i)->format('Y-m'));
        $byFund = DB::table('wf_fund_entries as e')->join('wf_funds as f', 'f.id', '=', 'e.fund_id')->where('e.organization_id', $org)->selectRaw("f.name, f.kind, sum(case when e.entry_type = 'donation' then e.amount else 0 end) as received, -sum(case when e.entry_type = 'disbursement' then e.amount else 0 end) as given")->groupBy('f.id', 'f.name', 'f.kind')->orderBy('f.name')->get();
        $byCategory = DB::table('wf_applications as a')->join('wf_grants as g', 'g.id', '=', 'a.grant_id')->join('wf_assessments as s', 's.id', '=', 'g.assessment_id')->where('a.organization_id', $org)->where('a.status', 'applied')->selectRaw('s.category, count(distinct a.patient_id) as patients, sum(a.amount) as total')->groupBy('s.category')->get();

        return view('admin.welfare.reports', ['months' => $months, 'in' => $in, 'out' => $out, 'byFund' => $byFund, 'byCategory' => $byCategory]);
    }
}
