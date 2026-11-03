<?php

namespace Modules\Watch\Http\Controllers\Web;

use App\Models\Watch\SvSignal;
use App\Services\TenantContextResolver;
use App\Services\Watch\SurveillanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignalController extends WatchController
{
    public function __construct(TenantContextResolver $tenant, private readonly SurveillanceService $surveillance)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'watch.signal.view');
        $rows = SvSignal::query()->with('condition')->where('organization_id', $this->organizationId())->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('unreported'), fn ($q) => $q->where('status', 'confirmed')->whereNull('reported_at'))->orderByRaw("case status when 'new' then 0 when 'investigating' then 1 when 'confirmed' then 2 else 3 end")->orderByDesc('raised_at')->paginate(25)->withQueryString();

        return view('admin.watch.signals.index', ['rows' => $rows, 'districts' => DB::table('geo_districts')->whereIn('id', $rows->pluck('district_id')->filter())->pluck('name_en', 'id'), 'canScan' => $request->user()->can('watch.signal.decide')]);
    }

    public function show(Request $request, string $signal)
    {
        $this->allow($request, 'watch.signal.view');
        $s = $this->find(SvSignal::class, $signal);
        $s->load('condition');
        $u = $request->user();

        return view('admin.watch.signals.show', ['s' => $s, 'district' => $s->district_id ? DB::table('geo_districts')->where('id', $s->district_id)->value('name_en') : null, 'authorities' => config('watch.authorities'),
            'can' => ['decide' => $u->can('watch.signal.decide'), 'report' => $u->can('watch.signal.report')]]);
    }

    public function investigate(Request $request, string $signal)
    {
        $this->allow($request, 'watch.signal.decide');
        $this->surveillance->investigate($this->find(SvSignal::class, $signal), $request->user());

        return back()->with('success', __('You are looking into this signal.'));
    }

    public function decide(Request $request, string $signal)
    {
        $this->allow($request, 'watch.signal.decide');
        $v = $request->validate(['outcome' => ['required', 'in:confirm,dismiss'], 'decision_note' => ['required', 'string', 'max:500']]);
        $this->surveillance->decide($this->find(SvSignal::class, $signal), $v['outcome'] === 'confirm', $v['decision_note'], $request->user());

        return back()->with('success', $v['outcome'] === 'confirm' ? __('Signal confirmed. Report it to the health authority.') : __('Signal dismissed.'));
    }

    public function report(Request $request, string $signal)
    {
        $this->allow($request, 'watch.signal.report');
        $v = $request->validate(['reported_to' => ['required', 'string', 'max:120'], 'report_reference' => ['nullable', 'string', 'max:60']]);
        $this->surveillance->report($this->find(SvSignal::class, $signal), $v['reported_to'], $v['report_reference'] ?? null, $request->user());

        return back()->with('success', __('Reported.'));
    }
}
