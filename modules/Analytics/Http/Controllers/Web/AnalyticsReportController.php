<?php

namespace Modules\Analytics\Http\Controllers\Web;

use App\Contracts\Documents\PdfRenderer;
use App\Models\Analytics\AnalyticsDelivery;
use App\Models\Analytics\AnalyticsDqIssue;
use App\Models\Analytics\AnalyticsDqRule;
use App\Models\Analytics\AnalyticsLoad;
use App\Models\Analytics\AnalyticsReport;
use App\Models\Analytics\AnalyticsSchedule;
use App\Models\Analytics\AnalyticsSource;
use App\Services\Analytics\DataQualityService;
use App\Services\Analytics\LoadRunner;
use App\Services\Analytics\Reports\ReportService;
use App\Services\Analytics\Reports\ScheduleService;
use App\Services\Analytics\Semantic\SemanticCatalog;
use App\Services\Analytics\Warehouse;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Saved reports and their schedules, the data-quality list, and the state of the warehouse loads. */
class AnalyticsReportController extends AnalyticsController
{
    public function __construct(TenantContextResolver $tenant, private readonly ReportService $reports, private readonly ScheduleService $schedules, private readonly DataQualityService $quality, private readonly LoadRunner $runner)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'analytics.report.view');

        return view('admin.analytics.reports', ['rows' => $this->reports->visibleTo($request->user(), $this->organizationId())]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'analytics.report.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:400'], 'query' => ['required', 'string'], 'shared' => ['nullable', 'boolean']]);
        $query = json_decode($d['query'], true);
        if (! is_array($query)) {
            throw ValidationException::withMessages(['query' => 'The query could not be read.']);
        }
        unset($query['from'], $query['to']);
        $r = $this->reports->save($this->organizationId(), $request->user(), $d['name'], $d['description'] ?? null, $query, $request->boolean('shared'));

        return redirect()->route('admin.analytics.reports.show', $r->public_id)->with('success', __('Report saved.'));
    }

    public function show(Request $request, string $report)
    {
        $this->allow($request, 'analytics.report.view');
        $r = $this->visible($request, $report);
        [$from, $to] = $this->range($request);
        $result = $this->reports->run($r, $this->scope($request), $from, $to);
        if ($request->query('export')) {
            $this->allow($request, 'analytics.report.export');
            $this->reports->auditRun($r, $request->user(), 'exported_'.$request->query('export'), count($result['rows']));
            if ($request->query('export') === 'pdf') {
                return response(app(PdfRenderer::class)->render($this->reports->toHtml($r->name, $result), 'a4'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.Str::slug($r->name).'.pdf"']);
            }

            return response($this->reports->toCsv($result), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.Str::slug($r->name).'.csv"']);
        }
        $schedules = AnalyticsSchedule::query()->where('report_id', $r->id)->get();

        return view('admin.analytics.report', ['r' => $r, 'result' => $result, 'from' => $from, 'to' => $to, 'schedules' => $schedules, 'catalogue' => SemanticCatalog::facts()]);
    }

    private function visible(Request $request, string $publicId): AnalyticsReport
    {
        $r = $this->find(AnalyticsReport::class, $publicId);
        abort_unless($r->shared || $r->created_by === $request->user()->id, 404);

        return $r;
    }

    public function destroy(Request $request, string $report)
    {
        $this->allow($request, 'analytics.report.manage');
        $this->reports->delete($this->visible($request, $report), $request->user());

        return redirect()->route('admin.analytics.reports')->with('success', __('Report deleted.'));
    }

    public function schedule(Request $request, string $report)
    {
        $this->allow($request, 'analytics.schedule.manage');
        $r = $this->visible($request, $report);
        $d = $request->validate(['frequency' => ['required', 'in:daily,weekly,monthly'], 'day' => ['nullable', 'integer'], 'at_time' => ['required', 'regex:/^\d{2}:\d{2}$/'], 'format' => ['required', 'in:csv,pdf'], 'recipients' => ['required', 'string', 'max:600']]);
        $this->schedules->create($r, $request->user(), $d['frequency'], isset($d['day']) ? (int) $d['day'] : null, $d['at_time'], $d['format'], preg_split('/[\s,;]+/', trim($d['recipients']), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return back()->with('success', __('Schedule saved.'));
    }

    public function toggleSchedule(Request $request, string $schedule)
    {
        $this->allow($request, 'analytics.schedule.manage');
        $s = $this->find(AnalyticsSchedule::class, $schedule);
        $s->update(['active' => ! $s->active, 'next_run_at' => ! $s->active ? $this->schedules->next($s) : $s->next_run_at]);

        return back()->with('success', __('Done.'));
    }

    public function schedules(Request $request)
    {
        $this->allow($request, 'analytics.schedule.manage');
        $rows = AnalyticsSchedule::query()->where('organization_id', $this->organizationId())->with('report:id,name,public_id')->latest('id')->get();

        return view('admin.analytics.schedules', ['rows' => $rows, 'deliveries' => AnalyticsDelivery::query()->whereIn('schedule_id', $rows->pluck('id'))->latest('id')->limit(30)->get()]);
    }

    // ---- data quality ------------------------------------------------------------------------------------------------------------------------

    public function quality(Request $request)
    {
        $this->allow($request, 'analytics.quality.view');
        $org = $this->organizationId();
        $issues = AnalyticsDqIssue::query()->where(fn ($q) => $q->where('organization_id', $org)->orWhereNull('organization_id'))->when($request->query('status', 'open'), fn ($q, $v) => $v === 'all' ? $q : $q->where('status', $v))->orderByRaw("case severity when 'error' then 0 else 1 end")->latest('last_seen_at')->paginate(30)->withQueryString();

        return view('admin.analytics.quality', ['issues' => $issues, 'status' => $request->query('status', 'open'), 'rules' => AnalyticsDqRule::query()->orderBy('code')->get()]);
    }

    public function runQuality(Request $request)
    {
        $this->allow($request, 'analytics.quality.manage');
        $r = $this->quality->run();

        return back()->with('success', __(':f rule(s) failing, :o new issue(s), :c cleared.', ['f' => $r['failing'], 'o' => $r['opened'], 'c' => $r['closed']]));
    }

    public function setAside(Request $request, AnalyticsDqIssue $issue)
    {
        $this->allow($request, 'analytics.quality.manage');
        abort_unless($issue->organization_id === null || $issue->organization_id === $this->organizationId(), 404);
        $d = $request->validate(['note' => ['required', 'string', 'max:300']]);
        $this->quality->setAside($issue, $request->user(), $d['note']);

        return back()->with('success', __('Set aside.'));
    }

    // ---- loads -------------------------------------------------------------------------------------------------------------------------------

    public function loads(Request $request)
    {
        $this->allow($request, 'analytics.load.view');

        return view('admin.analytics.loads', ['sources' => AnalyticsSource::query()->orderBy('kind')->orderBy('key')->get(), 'runs' => AnalyticsLoad::query()->latest('id')->limit(40)->get(), 'problems' => $this->runner->problems()->pluck('key')->all(), 'separate' => app(Warehouse::class)->isSeparate()]);
    }

    public function runLoads(Request $request)
    {
        $this->allow($request, 'analytics.load.run');
        $only = array_filter((array) $request->input('only', []));
        $runs = $this->runner->run($only ?: null, $request->user());
        $bad = collect($runs)->where('status', 'failed')->count();

        return back()->with($bad ? 'warning' : 'success', __(':n loader(s) ran, :b failed.', ['n' => count($runs), 'b' => $bad]));
    }

    public function rebuild(Request $request)
    {
        $this->allow($request, 'analytics.load.rebuild');
        $request->validate(['confirm' => ['required', 'in:REBUILD']]);
        $runs = $this->runner->rebuild($request->user());

        return back()->with('success', __('The warehouse was emptied and loaded again (:n loaders).', ['n' => count($runs)]));
    }
}
