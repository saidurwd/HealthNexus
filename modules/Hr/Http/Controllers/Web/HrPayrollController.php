<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrLoan;
use App\Models\Hr\HrPayComponent;
use App\Models\Hr\HrPaymentBatch;
use App\Models\Hr\HrPayrollAdjustment;
use App\Models\Hr\HrPayrollLine;
use App\Models\Hr\HrPayrollPeriod;
use App\Models\Hr\HrPayrollRun;
use App\Models\Hr\HrTaxSlab;
use App\Services\Hr\CompensationService;
use App\Services\Hr\HrPolicy;
use App\Services\Hr\PaymentFileService;
use App\Services\Hr\PayrollJournalService;
use App\Services\Hr\PayrollService;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pay components, loans, tax slabs, payroll runs and what comes out of them: exceptions, payslips, payment files, the journal. Salary data: payroll permissions only. */
class HrPayrollController extends HrController
{
    public function __construct(TenantContextResolver $tenant, private readonly PayrollService $payroll, private readonly CompensationService $compensation, private readonly PaymentFileService $files, private readonly PayrollJournalService $journals, private readonly HrPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function runs(Request $request)
    {
        $this->allow($request, 'payroll.run.view');
        $org = $this->organizationId();

        return view('admin.hr.payroll.runs', [
            'runs' => HrPayrollRun::with('period')->where('organization_id', $org)->latest('id')->paginate(20), 'periods' => HrPayrollPeriod::where('organization_id', $org)->orderByDesc('month_start')->limit(18)->get(),
            'lastMonths' => collect(range(1, 6))->map(fn ($i) => now()->subMonths($i)->startOfMonth()),
        ]);
    }

    public function createRun(Request $request): RedirectResponse
    {
        $this->allow($request, 'payroll.run.create');
        $v = $request->validate(['month' => ['required', 'date_format:Y-m'], 'run_type' => ['required', 'in:regular,supplementary'], 'reason' => ['nullable', 'string', 'max:300']]);
        $period = $this->payroll->openPeriod($this->organizationId(), CarbonImmutable::parse($v['month'].'-01'));
        $run = $this->payroll->createRun($request->user(), $period, $v['run_type'], $v['reason'] ?? null);
        $this->payroll->calculate($run);

        return redirect()->route('admin.hr.payroll.show', $run)->with('success', __('Run created and calculated.'));
    }

    public function show(Request $request, HrPayrollRun $run)
    {
        $this->scoped($request, $run, 'payroll.run.view');
        $lines = $run->lines()->with(['employee:id,public_id,employee_no,first_name,last_name,department_id', 'items'])->orderBy('employee_id')->paginate(60);

        return view('admin.hr.payroll.show', [
            'run' => $run->load('period'), 'lines' => $lines, 'exceptions' => $this->payroll->exceptions($run), 'batches' => HrPaymentBatch::where('run_id', $run->id)->get(), 'checks' => $run->status !== 'cancelled' ? $this->payroll->reconcile($run) : [],
            'adjustments' => HrPayrollAdjustment::with('employee')->where('period_id', $run->period_id)->latest('id')->limit(30)->get(), 'employees' => $this->employeeChoices(false),
        ]);
    }

    public function move(Request $request, HrPayrollRun $run): RedirectResponse
    {
        $action = (string) $request->input('action');
        $by = $request->user();
        switch ($action) {
            case 'calculate':
                $this->scoped($request, $run, 'payroll.run.create');
                $this->payroll->calculate($run);
                break;
            case 'approve':
                $this->scoped($request, $run, 'payroll.run.approve');
                $this->payroll->approve($run, $by);
                break;
            case 'cancel':
                $this->scoped($request, $run, 'payroll.run.create');
                $this->payroll->cancel($run, (string) $request->validate(['reason' => ['required', 'string', 'max:300']])['reason']);
                break;
            case 'publish':
                $this->scoped($request, $run, 'payroll.payslip.publish');
                $this->payroll->publishPayslips($run);
                break;
            case 'paid':
                $this->scoped($request, $run, 'payroll.run.approve');
                $this->payroll->markPaid($run);
                break;
            case 'journal':
                $this->scoped($request, $run, 'payroll.journal.post');
                $taken = $this->journals->post($run);

                return back()->with($taken ? 'success' : 'warning', $taken ? __('Journal posted to Finance.') : __('Finance is not switched on yet; the journal stays on the run.'));
            default:
                abort(422);
        }

        return back()->with('success', __('Done.'));
    }

    public function prepareFile(Request $request, HrPayrollRun $run): RedirectResponse
    {
        $this->scoped($request, $run, 'payroll.payment.file');
        $v = $request->validate(['method' => ['required', 'in:bank,mobile_wallet']]);
        $this->files->prepare($run, $v['method'], $request->user());

        return back()->with('success', __('Payment file prepared.'));
    }

    public function downloadFile(Request $request, HrPaymentBatch $batch)
    {
        $this->scoped($request, $batch, 'payroll.payment.file');
        $csv = $this->files->download($batch, $request->user());

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="salary-'.$batch->method.'-'.$batch->id.'.csv"']);
    }

    public function addAdjustment(Request $request, HrPayrollRun $run): RedirectResponse
    {
        $this->scoped($request, $run, 'payroll.adjustment.manage');
        $v = $request->validate(['employee' => ['required', 'string'], 'kind' => ['required', 'in:earning,deduction'], 'name' => ['required', 'string', 'max:100'], 'amount' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:300']]);
        $this->payroll->addAdjustment($this->findEmployee($v['employee']), $run->period, $v['kind'], $v['name'], (string) $v['amount'], $v['reason'], $request->user());

        return back()->with('success', __('Adjustment saved. Calculate the run again, or pay it in a supplementary run.'));
    }

    public function payslip(Request $request, HrPayrollLine $line)
    {
        $this->scoped($request, $line, 'payroll.payslip.view');

        return view('admin.hr.payslip', ['line' => $line->load(['run.period', 'employee', 'items']), 'back' => route('admin.hr.payroll.show', $line->run)]);
    }

    // ---- compensation set-up ----

    public function components(Request $request)
    {
        $this->allow($request, 'compensation.structure.view');
        $org = $this->organizationId();

        return view('admin.hr.payroll.components', [
            'components' => HrPayComponent::where('organization_id', $org)->orderBy('kind')->orderBy('code')->get(), 'slabs' => HrTaxSlab::where('organization_id', $org)->orderByDesc('effective_from')->orderBy('from_amount')->get()->groupBy(fn ($s) => $s->effective_from->toDateString()),
            'exampleSlabs' => $this->policy->get('tax_slabs'), 'loans' => HrLoan::with('employee')->where('organization_id', $org)->orderByRaw("status = 'requested' desc")->latest('id')->limit(30)->get(), 'employees' => $this->employeeChoices(),
        ]);
    }

    public function storeComponent(Request $request): RedirectResponse
    {
        $this->allow($request, 'compensation.component.manage');
        $v = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:100'], 'kind' => ['required', 'in:earning,deduction,employer'], 'method' => ['required', 'in:fixed,percent_basic,percent_gross'], 'taxable' => ['nullable', 'boolean'], 'prorate' => ['nullable', 'boolean']]);
        HrPayComponent::updateOrCreate(['organization_id' => $this->organizationId(), 'code' => strtoupper($v['code'])], ['name' => $v['name'], 'kind' => $v['kind'], 'method' => $v['method'], 'taxable' => $request->boolean('taxable'), 'prorate' => $request->boolean('prorate')]);

        return back()->with('success', __('Component saved.'));
    }

    public function storeSlabs(Request $request): RedirectResponse
    {
        $this->allow($request, 'payroll.tax.manage');
        $v = $request->validate(['effective_from' => ['required', 'date'], 'slabs' => ['required', 'string', 'max:2000']]);
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($v['slabs'])) ?: [] as $line) {
            [$from, $to, $rate] = array_pad(array_map('trim', explode(',', $line)), 3, '');
            if ($from === '') {
                continue;
            }
            $rows[] = [$from, $to === '' ? null : $to, $rate];
        }
        $this->compensation->setSlabs($this->organizationId(), CarbonImmutable::parse($v['effective_from']), $rows);

        return back()->with('success', __('Tax slabs saved from the date given.'));
    }

    public function requestLoan(Request $request): RedirectResponse
    {
        $this->allow($request, 'payroll.loan.manage');
        $v = $request->validate(['employee' => ['required', 'string'], 'kind' => ['required', 'in:loan,advance'], 'amount' => ['required', 'numeric', 'gt:0'], 'monthly_deduction' => ['required', 'numeric', 'gt:0'], 'start_month' => ['required', 'date'], 'reason' => ['nullable', 'string', 'max:200']]);
        $this->compensation->requestLoan($this->findEmployee($v['employee']), $v['kind'], (string) $v['amount'], (string) $v['monthly_deduction'], CarbonImmutable::parse($v['start_month']), $v['reason'] ?? null);

        return back()->with('success', __('Loan request saved.'));
    }

    public function approveLoan(Request $request, HrLoan $loan): RedirectResponse
    {
        $this->scoped($request, $loan, 'payroll.loan.manage');
        $this->compensation->approveLoan($loan, $request->user());

        return back()->with('success', __('Loan approved.'));
    }
}
