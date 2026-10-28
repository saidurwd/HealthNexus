<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrAppraisal;
use App\Models\Hr\HrAppraisalCycle;
use App\Models\Hr\HrCandidate;
use App\Models\Hr\HrJob;
use App\Models\Hr\HrPosition;
use App\Models\Hr\HrProviderLink;
use App\Models\Hr\HrTraining;
use App\Models\Hr\HrVacancy;
use App\Services\Hr\ContractService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\HrBackfillService;
use App\Services\Hr\HrPolicy;
use App\Services\Hr\HrReportService;
use App\Services\Hr\JobService;
use App\Services\Hr\PerformanceService;
use App\Services\Hr\ProviderLinkService;
use App\Services\Hr\RecruitmentService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Dashboard, jobs and positions, the provider-link review, recruitment, training and appraisals, and the people reports. */
class HrDeskController extends HrController
{
    public function __construct(
        TenantContextResolver $tenant,
        private readonly HrReportService $reports,
        private readonly JobService $jobs,
        private readonly ProviderLinkService $links,
        private readonly RecruitmentService $recruitment,
        private readonly PerformanceService $performance,
        private readonly ContractService $contracts,
        private readonly HrBackfillService $backfill,
        private readonly HrPolicy $policy,
    ) {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request)
    {
        $this->allow($request, 'hr.dashboard.view');
        $org = $this->organizationId();

        return view('admin.hr.dashboard', ['d' => $this->reports->dashboard($org), 'byDepartment' => $this->reports->headcountByDepartment($org), 'expiringContracts' => $this->contracts->expiring($org, 30)]);
    }

    public function jobs(Request $request)
    {
        $this->allow($request, 'hr.employee.view');
        $org = $this->organizationId();

        return view('admin.hr.jobs', [
            'jobs' => HrJob::query()->where('organization_id', $org)->orderBy('title')->get(), 'families' => $this->policy->get('job_families'), 'credentialTypes' => $this->policy->get('credential_types'),
            'positions' => HrPosition::query()->with('job:id,title')->where('organization_id', $org)->orderBy('code')->get()->each(fn ($p) => $p->setAttribute('filled', app(EmployeeService::class)->headcount($p)['filled'])), 'departments' => $this->departmentChoices(),
        ]);
    }

    public function storeJob(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.job.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:30'], 'title' => ['required', 'string', 'max:150'], 'family' => ['required', Rule::in($this->policy->get('job_families'))], 'grade' => ['nullable', 'string', 'max:20'], 'required_credentials' => ['nullable', 'array']]);
        $this->jobs->createJob($this->organizationId(), $v['code'], $v['title'], $v['family'], $v['grade'] ?? null, $v['required_credentials'] ?? []);

        return back()->with('success', __('Job added.'));
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.job.manage');
        $v = $request->validate(['job_id' => ['required', 'integer'], 'code' => ['required', 'string', 'max:30'], 'title' => ['required', 'string', 'max:150'], 'department_id' => ['nullable', 'integer'], 'headcount' => ['required', 'integer', 'min:1', 'max:500']]);
        $job = HrJob::where('organization_id', $this->organizationId())->findOrFail($v['job_id']);
        $this->jobs->createPosition($job, $v['code'], $v['title'], isset($v['department_id']) ? $this->departmentChoices()->firstWhere('id', (int) $v['department_id'])?->id : null, (int) $v['headcount']);

        return back()->with('success', __('Position added.'));
    }

    public function providerLinks(Request $request)
    {
        $this->allow($request, 'hr.provider_link.review');

        return view('admin.hr.provider-links', ['groups' => $this->links->review($this->organizationId()), 'accepted' => HrProviderLink::with(['provider', 'employee'])->where(['organization_id' => $this->organizationId(), 'status' => 'accepted'])->latest('id')->limit(15)->get()]);
    }

    public function proposeLinks(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.provider_link.review');
        $n = $this->links->propose($this->organizationId());

        return back()->with('success', trans_choice(':n suggestion found.|:n suggestions found.', $n, ['n' => $n]));
    }

    public function decideLink(Request $request, HrProviderLink $link): RedirectResponse
    {
        $this->scoped($request, $link, 'hr.provider_link.review');
        $request->input('decision') === 'accept' ? $this->links->accept($link, $request->user()) : $this->links->reject($link, $request->user());

        return back()->with('success', __('Decision saved.'));
    }

    public function backfill(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.settings.manage');
        $r = $this->backfill->fromUsers($this->organizationId(), $request->user());

        return back()->with('success', __(':c employee record(s) made from existing logins, :p provider suggestion(s) to review.', ['c' => $r['created'], 'p' => $r['proposed']]));
    }

    public function vacancies(Request $request)
    {
        $this->allow($request, 'hr.recruitment.manage');
        $org = $this->organizationId();

        return view('admin.hr.vacancies', ['vacancies' => HrVacancy::with('candidates')->where('organization_id', $org)->latest('id')->get(), 'positions' => HrPosition::where('organization_id', $org)->orderBy('code')->get(), 'departments' => $this->departmentChoices(), 'jobs' => $this->jobChoices()]);
    }

    public function storeVacancy(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.recruitment.manage');
        $v = $request->validate(['title' => ['required', 'string', 'max:150'], 'position_id' => ['nullable', 'integer'], 'openings' => ['required', 'integer', 'min:1', 'max:100'], 'closes_on' => ['nullable', 'date']]);
        $position = isset($v['position_id']) ? HrPosition::where('organization_id', $this->organizationId())->findOrFail($v['position_id']) : null;
        $this->recruitment->openVacancy($this->organizationId(), $v['title'], $position, (int) $v['openings'], $v['closes_on'] ?? null);

        return back()->with('success', __('Vacancy opened.'));
    }

    public function storeCandidate(Request $request, HrVacancy $vacancy): RedirectResponse
    {
        $this->scoped($request, $vacancy, 'hr.recruitment.manage');
        $v = $request->validate(['name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:150']]);
        $this->recruitment->addCandidate($vacancy, $v['name'], $v['phone'] ?? null, $v['email'] ?? null);

        return back()->with('success', __('Candidate added.'));
    }

    public function moveCandidate(Request $request, HrCandidate $candidate): RedirectResponse
    {
        $this->scoped($request, $candidate, 'hr.recruitment.manage');
        if ($request->input('stage') === 'hired') {
            $v = $request->validate(['joined_on' => ['required', 'date']]);
            $employee = $this->recruitment->hire($candidate, $request->user(), ['joined_on' => $v['joined_on'], 'branch_id' => $this->tenant->getBranchId()]);

            return redirect()->route('admin.hr.employees.show', $employee)->with('success', __('Hired. Finish the onboarding checklist.'));
        }
        $this->recruitment->advance($candidate, (string) $request->input('stage'), $request->input('notes'));

        return back()->with('success', __('Candidate moved.'));
    }

    public function training(Request $request)
    {
        $this->allow($request, 'hr.training.manage');
        $org = $this->organizationId();

        return view('admin.hr.training', [
            'trainings' => HrTraining::withCount('records')->where('organization_id', $org)->latest('held_on')->get(), 'cycles' => HrAppraisalCycle::where('organization_id', $org)->latest('id')->get(),
            'appraisals' => HrAppraisal::with(['employee', 'cycle'])->where('organization_id', $org)->where('status', 'pending')->latest('id')->limit(40)->get(), 'employees' => $this->employeeChoices(),
        ]);
    }

    public function storeTraining(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.training.manage');
        $v = $request->validate(['title' => ['required', 'string', 'max:150'], 'held_on' => ['required', 'date'], 'mandatory' => ['nullable', 'boolean'], 'valid_months' => ['nullable', 'integer', 'min:1', 'max:120']]);
        $this->performance->createTraining($this->organizationId(), $v['title'], $v['held_on'], $request->boolean('mandatory'), $v['valid_months'] ?? null);

        return back()->with('success', __('Training added.'));
    }

    public function recordTraining(Request $request, HrTraining $training): RedirectResponse
    {
        $this->scoped($request, $training, 'hr.training.manage');
        $v = $request->validate(['employee' => ['required', 'string'], 'outcome' => ['required', 'in:attended,passed,failed,absent']]);
        $this->performance->record($training, $this->findEmployee($v['employee']), $v['outcome']);

        return back()->with('success', __('Attendance recorded.'));
    }

    public function openCycle(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.appraisal.manage');
        $v = $request->validate(['name' => ['required', 'string', 'max:100'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date']]);
        $this->performance->openCycle($this->organizationId(), $v['name'], $v['period_start'], $v['period_end']);

        return back()->with('success', __('Appraisal cycle opened.'));
    }

    public function submitAppraisal(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $this->scoped($request, $appraisal, 'hr.appraisal.manage');
        $v = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'strengths' => ['nullable', 'string', 'max:400'], 'improvements' => ['nullable', 'string', 'max:400']]);
        $reviewer = $this->me($request);
        $this->performance->submit($appraisal, $reviewer, (int) $v['rating'], $v['strengths'] ?? null, $v['improvements'] ?? null);

        return back()->with('success', __('Appraisal submitted.'));
    }

    public function reports(Request $request)
    {
        $this->allow($request, 'hr.report.view');
        $org = $this->organizationId();
        $report = in_array($request->query('report'), ['headcount', 'turnover', 'absenteeism', 'credentials', 'overtime', 'cost'], true) ? $request->query('report') : 'headcount';
        $month = CarbonImmutable::parse($request->query('month', now()->subMonth()->format('Y-m').'-01'));
        if ($report === 'cost') {
            $this->allow($request, 'payroll.run.view');
        }
        $data = match ($report) {
            'headcount' => $this->reports->headcountByDepartment($org),
            'turnover' => collect($this->reports->turnover($org, (int) $request->query('year', now()->year)))->map(fn ($r) => (object) $r),
            'absenteeism' => $this->reports->absenteeism($org, $month),
            'credentials' => $this->reports->credentialCompliance($org),
            'overtime' => $this->reports->overtime($org, $month->startOfMonth(), $month->endOfMonth()),
            default => $this->reports->payrollCost($org),
        };
        if ($request->query('export') === 'csv') {
            $this->allow($request, 'hr.report.export');

            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                $first = $data->first();
                $first && fputcsv($out, array_keys((array) $first));
                foreach ($data as $row) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, (array) $row));
                }
                fclose($out);
            }, "hr-$report.csv", ['Content-Type' => 'text/csv']);
        }

        return view('admin.hr.reports', ['report' => $report, 'data' => $data, 'month' => $month, 'year' => (int) $request->query('year', now()->year)]);
    }
}
