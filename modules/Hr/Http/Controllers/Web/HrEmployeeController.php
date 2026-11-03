<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Models\Hr\HrChecklistItem;
use App\Models\Hr\HrCredential;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrPayComponent;
use App\Models\Hr\HrSalaryStructure;
use App\Models\User;
use App\Services\Hr\ChecklistService;
use App\Services\Hr\CompensationService;
use App\Services\Hr\ContractService;
use App\Services\Hr\CredentialService;
use App\Services\Hr\EmployeeService;
use App\Services\Hr\HrPolicy;
use App\Services\TenantContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The employee register: profile, history, status, contracts, licences, documents, checklists and (for those allowed) pay. */
class HrEmployeeController extends HrController
{
    public function __construct(TenantContextResolver $tenant, private readonly EmployeeService $employees, private readonly CredentialService $credentials, private readonly ContractService $contracts, private readonly ChecklistService $checklists, private readonly CompensationService $compensation, private readonly HrPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'hr.employee.view');
        $org = $this->organizationId();
        $rows = HrEmployee::query()->with(['department:id,name', 'job:id,title'])->where('organization_id', $org)
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('employee_no', 'like', "%$v%")->orWhere('first_name', 'like', "%$v%")->orWhere('last_name', 'like', "%$v%")))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v), fn ($q) => $q->whereIn('status', ['onboarding', 'active', 'on_leave', 'suspended']))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department_id', $v))->orderBy('employee_no')->paginate(30)->withQueryString();

        return view('admin.hr.employees.index', ['rows' => $rows, 'departments' => $this->departmentChoices(), 'statuses' => $this->policy->get('employee_statuses'), 'categories' => $this->policy->get('employee_categories'), 'jobs' => $this->jobChoices(), 'managers' => $this->employeeChoices()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->allow($request, 'hr.employee.manage');
        $v = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'gender' => ['nullable', 'in:male,female,other'], 'date_of_birth' => ['nullable', 'date'], 'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'], 'department_id' => ['nullable', 'integer'], 'job_id' => ['nullable', 'integer'], 'manager_id' => ['nullable', 'integer'], 'category' => ['required', Rule::in($this->policy->get('employee_categories'))],
            'joined_on' => ['required', 'date'], 'weekly_off' => ['nullable', 'in:sat,sun,mon,tue,wed,thu,fri'], 'device_code' => ['nullable', 'string', 'max:40'],
        ]);
        $employee = $this->employees->create($request->user(), $this->organizationId(), array_filter($v, fn ($x) => $x !== null) + ['branch_id' => $this->tenant->getBranchId()]);
        $this->checklists->open($employee, 'onboarding', $request->user());

        return redirect()->route('admin.hr.employees.show', $employee)->with('success', __('Employee added. Finish the onboarding checklist, then make them active.'));
    }

    public function show(Request $request, HrEmployee $employee)
    {
        $this->scoped($request, $employee, 'hr.employee.view');
        $sensitive = $request->user()->can('hr.employee.sensitive');
        $salary = $request->user()->can('compensation.structure.view');

        return view('admin.hr.employees.show', [
            'employee' => $employee->load(['department', 'job', 'position', 'manager', 'user']), 'sensitive' => $sensitive, 'salary' => $salary, 'history' => $employee->history()->latest('id')->limit(30)->get(), 'contracts' => $employee->contracts()->latest('start_on')->get(),
            'credentials' => $employee->credentials()->orderBy('credential_type')->get(), 'documents' => $employee->documents()->latest('id')->get(), 'checklists' => $employee->checklists()->with('items')->latest('id')->get(), 'gaps' => $this->credentials->gaps($employee),
            'structures' => $salary ? HrSalaryStructure::with('lines.component')->where('employee_id', $employee->id)->orderByDesc('version')->get() : collect(), 'components' => $salary ? HrPayComponent::where(['organization_id' => $this->organizationId(), 'status' => 'active'])->orderBy('code')->get() : collect(),
            'departments' => $this->departmentChoices(), 'jobs' => $this->jobChoices(), 'managers' => $this->employeeChoices()->where('id', '!=', $employee->id), 'credentialTypes' => $this->policy->get('credential_types'), 'documentTypes' => $this->policy->get('document_types'),
            'categories' => $this->policy->get('employee_categories'), 'users' => User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->whereNotIn('id', HrEmployee::whereNotNull('user_id')->pluck('user_id'))->orderBy('name')->get(['id', 'name', 'email']),
            'mask' => fn ($v) => self::mask($v),
        ]);
    }

    public function update(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.employee.manage');
        $v = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:150'], 'department_id' => ['nullable', 'integer'],
            'job_id' => ['nullable', 'integer'], 'manager_id' => ['nullable', 'integer'], 'category' => ['sometimes', Rule::in($this->policy->get('employee_categories'))], 'weekly_off' => ['nullable', 'in:sat,sun,mon,tue,wed,thu,fri'],
            'device_code' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:300'], 'emergency_contact' => ['nullable', 'string', 'max:150'], 'reason' => ['nullable', 'string', 'max:300'],
            'national_id' => ['nullable', 'string', 'max:40'], 'tin' => ['nullable', 'string', 'max:40'], 'bank_name' => ['nullable', 'string', 'max:100'], 'bank_account' => ['nullable', 'string', 'max:40'], 'mobile_wallet' => ['nullable', 'string', 'max:40'],
        ]);
        $reason = $v['reason'] ?? null;
        unset($v['reason']);
        if (! $request->user()->can('hr.employee.sensitive')) {
            $v = array_diff_key($v, array_flip(['national_id', 'tin', 'bank_name', 'bank_account', 'mobile_wallet']));
        }
        // an empty box means "leave as it is" for the sensitive values (they are never sent back to the page)
        foreach (['national_id', 'tin', 'bank_account', 'mobile_wallet'] as $field) {
            if (array_key_exists($field, $v) && $v[$field] === null) {
                unset($v[$field]);
            }
        }
        $this->employees->update($employee, $v, $request->user(), $reason);

        return back()->with('success', __('Saved.'));
    }

    public function status(Request $request, HrEmployee $employee): RedirectResponse
    {
        $action = $request->input('action');
        $this->scoped($request, $employee, in_array($action, ['resigned', 'terminated', 'retired', 'rehire'], true) ? 'hr.employee.separate' : 'hr.employee.manage');
        $v = $request->validate(['reason' => ['nullable', 'string', 'max:300'], 'date' => ['nullable', 'date']]);
        $on = CarbonImmutable::parse($v['date'] ?? now()->toDateString());
        if ($action === 'rehire') {
            $this->employees->rehire($employee, $on, $request->user(), $v['reason'] ?? null);
            $this->checklists->open($employee->refresh(), 'onboarding', $request->user());
        } elseif (in_array($action, ['resigned', 'terminated', 'retired'], true)) {
            $this->employees->separate($employee, $action, $on, (string) ($v['reason'] ?? ''), $request->user());
            $this->checklists->open($employee->refresh(), 'offboarding', $request->user());
        } else {
            $this->employees->changeStatus($employee, (string) $action, $request->user(), $v['reason'] ?? null, $on);
        }

        return back()->with('success', __('Status updated.'));
    }

    public function linkUser(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.employee.manage');
        $v = $request->validate(['user_id' => ['required', 'integer']]);
        $this->employees->linkUser($employee, User::findOrFail($v['user_id']));

        return back()->with('success', __('Login linked.'));
    }

    public function addContract(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.contract.manage');
        $v = $request->validate(['contract_type' => ['required', 'in:permanent,fixed_term,probation,visiting'], 'start_on' => ['required', 'date'], 'end_on' => ['nullable', 'date'], 'notice_days' => ['nullable', 'integer', 'min:0', 'max:365'], 'probation_months' => ['nullable', 'integer', 'min:0', 'max:24'], 'terms' => ['nullable', 'string', 'max:300']]);
        $this->contracts->start($employee, $v['contract_type'], $v['start_on'], $v['end_on'] ?? null, $request->user(), (int) ($v['notice_days'] ?? 30), (int) ($v['probation_months'] ?? 0), $v['terms'] ?? null);

        return back()->with('success', __('Contract saved.'));
    }

    public function addCredential(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.credential.manage');
        $v = $request->validate(['credential_type' => ['required', Rule::in($this->policy->get('credential_types'))], 'number' => ['nullable', 'string', 'max:80'], 'issuing_body' => ['nullable', 'string', 'max:120'], 'issued_on' => ['nullable', 'date'], 'expires_on' => ['nullable', 'date']]);
        $this->credentials->add($employee, $v['credential_type'], $v['number'] ?? null, $v['issuing_body'] ?? null, $v['issued_on'] ?? null, $v['expires_on'] ?? null);

        return back()->with('success', __('Licence recorded.'));
    }

    public function verifyCredential(Request $request, HrCredential $credential): RedirectResponse
    {
        $this->scoped($request, $credential, 'hr.credential.manage');
        $this->credentials->verify($credential, $request->user());

        return back()->with('success', __('Marked as checked with the issuing body.'));
    }

    public function addDocument(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.document.manage');
        $v = $request->validate(['doc_type' => ['required', Rule::in($this->policy->get('document_types'))], 'title' => ['required', 'string', 'max:150'], 'number' => ['nullable', 'string', 'max:80'], 'expires_on' => ['nullable', 'date']]);
        $employee->documents()->create($v + ['organization_id' => $employee->organization_id, 'uploaded_by' => $request->user()->id]);

        return back()->with('success', __('Document recorded.'));
    }

    public function tick(Request $request, HrChecklistItem $item): RedirectResponse
    {
        $employee = HrEmployee::findOrFail($item->checklist->employee_id);
        $this->scoped($request, $employee, 'hr.onboarding.manage');
        $this->checklists->tick($item, $request->user(), $request->input('note'));

        return back()->with('success', __('Ticked.'));
    }

    public function openChecklist(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.onboarding.manage');
        $v = $request->validate(['kind' => ['required', 'in:onboarding,offboarding']]);
        $this->checklists->open($employee, $v['kind'], $request->user());

        return back()->with('success', __('Checklist opened.'));
    }

    public function revokeAccess(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.employee.separate');
        $this->checklists->revokeAccess($employee, $request->user(), 'Switched off by HR');

        return back()->with('warning', __('Login, roles and provider record switched off.'));
    }

    public function newStructure(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'compensation.structure.manage');
        $v = $request->validate(['effective_from' => ['required', 'date'], 'reason' => ['nullable', 'string', 'max:200'], 'lines' => ['required', 'array'], 'lines.*.amount' => ['nullable', 'numeric', 'min:0'], 'lines.*.percent' => ['nullable', 'numeric', 'between:0,100']]);
        $lines = [];
        foreach ($v['lines'] as $code => $row) {
            if (($row['amount'] ?? '') !== '' && ($row['amount'] ?? null) !== null || ($row['percent'] ?? '') !== '' && ($row['percent'] ?? null) !== null) {
                $lines[] = ['component' => $code, 'amount' => $row['amount'] ?? null, 'percent' => $row['percent'] ?? null];
            }
        }
        $this->compensation->newStructure($employee, CarbonImmutable::parse($v['effective_from']), $lines, $request->user(), $v['reason'] ?? null);

        return back()->with('success', __('New salary structure saved.'));
    }

    public function addDependent(Request $request, HrEmployee $employee): RedirectResponse
    {
        $this->scoped($request, $employee, 'hr.employee.manage');
        $v = $request->validate(['name' => ['required', 'string', 'max:150'], 'relation' => ['required', 'string', 'max:20'], 'date_of_birth' => ['nullable', 'date']]);
        $this->compensation->addDependent($employee, $v['name'], $v['relation'], $v['date_of_birth'] ?? null);

        return back()->with('success', __('Dependent added.'));
    }
}
