<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmForm;
use App\Models\Crm\CrmFormSubmission;
use App\Models\Patient;
use App\Models\User;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FormService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Complaints and digital forms (S11). */
class CrmComplaintController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly ComplaintService $complaints, private readonly FormService $forms)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'crm.complaint.view');
        $status = $request->query('status', 'open');
        $rows = CrmComplaint::query()->with(['patient:id,first_name,last_name,enterprise_patient_no', 'assignee:id,name'])->where('organization_id', $this->organizationId())
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['open', 'assigned', 'investigating', 'escalated']))->when($status !== 'open' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("case severity when 'high' then 0 when 'medium' then 1 else 2 end")->orderBy('due_at')->paginate(25)->withQueryString();

        return view('admin.crm.complaints', ['rows' => $rows, 'status' => $status]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'crm.complaint.manage');
        $d = $request->validate(['patient_no' => ['nullable', 'string', 'max:60'], 'channel' => ['required', 'string', 'max:10'], 'category' => ['required', 'string', 'max:20'], 'severity' => ['required', 'in:low,medium,high'], 'description' => ['required', 'string', 'max:4000']]);
        $patient = ! empty($d['patient_no']) ? Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first() : null;
        if (! empty($d['patient_no']) && $patient === null) {
            return back()->withInput()->withErrors(['patient_no' => __('No patient has that number.')]);
        }
        $c = $this->complaints->lodge($this->organizationId(), $d, $request->user(), $patient);

        return redirect()->route('admin.crm.complaints.show', $c->public_id)->with('success', __('Complaint :n recorded.', ['n' => $c->number]));
    }

    public function show(Request $request, string $complaint)
    {
        $this->allow($request, 'crm.complaint.view');
        $row = $this->find(CrmComplaint::class, $complaint)->load(['patient', 'assignee']);

        return view('admin.crm.complaint', ['c' => $row, 'events' => $row->events()->with('user:id,name')->orderBy('id')->get(), 'staff' => User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function action(Request $request, string $complaint)
    {
        $row = $this->find(CrmComplaint::class, $complaint);
        $d = $request->validate(['action' => ['required', 'in:assign,investigate,resolve,close'], 'assigned_to' => ['nullable', 'integer', 'exists:users,id'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->allow($request, $d['action'] === 'close' ? 'crm.complaint.close' : 'crm.complaint.manage');
        match ($d['action']) {
            'assign' => $this->complaints->assign($row, User::query()->findOrFail($d['assigned_to'] ?? 0), $request->user()),
            'investigate' => $this->complaints->investigate($row, $request->user(), $d['note'] ?? null),
            'resolve' => $this->complaints->resolve($row, $request->user(), (string) ($d['note'] ?? '')),
            default => $this->complaints->close($row, $request->user()),
        };

        return back()->with('success', __('Saved.'));
    }

    public function forms(Request $request)
    {
        $this->allow($request, 'crm.form.view');

        return view('admin.crm.forms', ['rows' => CrmForm::query()->where('organization_id', $this->organizationId())->orderBy('title')->get(), 'inbox' => CrmFormSubmission::query()->with(['form:id,title', 'patient:id,first_name,last_name,enterprise_patient_no'])->where(['organization_id' => $this->organizationId(), 'status' => 'submitted'])->latest('id')->limit(30)->get()]);
    }

    public function storeForm(Request $request)
    {
        $this->allow($request, 'crm.form.manage');
        $d = $request->validate(['code' => ['required', 'regex:/^[a-z0-9-]{2,40}$/'], 'title' => ['required', 'string', 'max:150'], 'fields' => ['required', 'string', 'max:6000']]);
        // One question per line: key | label | type | required(y/n) | option,option
        $fields = [];
        foreach (preg_split('/\R/', trim($d['fields'])) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $p = array_map('trim', explode('|', $line));
            $fields[] = array_filter(['key' => $p[0], 'label' => $p[1] ?? '', 'type' => $p[2] ?? 'text', 'required' => ($p[3] ?? 'n') === 'y', 'options' => isset($p[4]) && $p[4] !== '' ? array_map('trim', explode(',', $p[4])) : null], fn ($v) => $v !== null);
        }
        $this->forms->define($this->organizationId(), $d['code'], $d['title'], $fields);

        return back()->with('success', __('Saved.'));
    }

    public function submission(Request $request, string $submission)
    {
        $this->allow($request, 'crm.form.view');
        $row = $this->find(CrmFormSubmission::class, $submission)->load(['form', 'patient']);

        return view('admin.crm.submission', ['s' => $row]);
    }

    public function reviewSubmission(Request $request, string $submission)
    {
        $this->allow($request, 'crm.form.manage');
        $this->forms->review($this->find(CrmFormSubmission::class, $submission), $request->user());

        return redirect()->route('admin.crm.forms')->with('success', __('Saved.'));
    }
}
