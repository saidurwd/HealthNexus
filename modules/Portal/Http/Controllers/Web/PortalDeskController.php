<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalAccessLog;
use App\Models\Portal\PortalDocument;
use App\Models\Portal\PortalOutbox;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalSecurityEvent;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalPolicy;
use App\Services\Portal\PortalReportService;
use App\Services\Portal\ProxyService;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The front-desk side of the patient portal: logins, relatives' access, what was sent, who looked at what, and the settings (S01, S02, S08, S10). */
class PortalDeskController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly PortalAuthService $auth, private readonly ProxyService $proxies, private readonly PortalReportService $reports,
        private readonly SettingsService $settings, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request)
    {
        $this->allow($request, 'portal.dashboard.view');

        return view('admin.portal.dashboard', ['d' => $this->reports->portal($this->organizationId())]);
    }

    public function accounts(Request $request)
    {
        $this->allow($request, 'portal.account.view');
        $q = trim((string) $request->query('q', ''));
        $rows = PatientPortalAccount::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('phone', 'like', '%'.preg_replace('/\D/', '', $q).'%')->orWhereHas('patient', fn ($p) => $p->where('enterprise_patient_no', 'like', "%$q%")->orWhere('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%"))))
            ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))->latest('id')->paginate(25)->withQueryString();

        return view('admin.portal.accounts', ['rows' => $rows, 'q' => $q, 'code' => session('portal_invitation_code')]);
    }

    public function invite(Request $request)
    {
        $this->allow($request, 'portal.account.manage');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:60']]);
        $patient = Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first();
        if ($patient === null) {
            return back()->withErrors(['patient_no' => __('No patient has that number.')]);
        }
        [, $code] = $this->auth->invite($patient, $request->user()->id);

        return redirect()->route('admin.portal.accounts')->with('success', __('Invitation made for :n. Give the patient this code after checking who they are. It is shown only now.', ['n' => $patient->enterprise_patient_no]))->with('portal_invitation_code', $code);
    }

    public function show(Request $request, string $account)
    {
        $this->allow($request, 'portal.account.view');
        $row = $this->find(PatientPortalAccount::class, $account)->load('patient');

        return view('admin.portal.account', [
            'a' => $row, 'devices' => $row->devices()->orderByDesc('last_seen_at')->get(),
            'events' => PortalSecurityEvent::query()->where('account_id', $row->id)->latest('id')->limit(30)->get(),
            'proxiesOut' => PortalProxy::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->where('account_id', $row->id)->latest('id')->get(),
            'proxiesIn' => PortalProxy::query()->with('account.patient:id,first_name,last_name')->where('patient_id', $row->patient_id)->latest('id')->get(),
        ]);
    }

    public function status(Request $request, string $account)
    {
        $this->allow($request, 'portal.account.manage');
        $row = $this->find(PatientPortalAccount::class, $account);
        $d = $request->validate(['status' => ['required', 'in:active,locked,suspended,closed'], 'reason' => ['nullable', 'string', 'max:200']]);
        $this->auth->setStatus($row, $d['status'], $d['reason'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function signOutEverywhere(Request $request, string $account)
    {
        $this->allow($request, 'portal.account.manage');
        $this->auth->revokeAll($this->find(PatientPortalAccount::class, $account), 'signed out by staff');

        return back()->with('success', __('Every device was signed out.'));
    }

    public function proxies(Request $request)
    {
        $this->allow($request, 'portal.proxy.review');
        $status = $request->query('status', 'requested');
        $rows = PortalProxy::query()->with(['account.patient:id,first_name,last_name,enterprise_patient_no,phone', 'patient:id,first_name,last_name,enterprise_patient_no,date_of_birth'])->where('organization_id', $this->organizationId())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString();

        return view('admin.portal.proxies', ['rows' => $rows, 'status' => $status]);
    }

    public function decideProxy(Request $request, string $proxy)
    {
        $this->allow($request, 'portal.proxy.review');
        $row = $this->find(PortalProxy::class, $proxy);
        $d = $request->validate(['decision' => ['required', 'in:approve,reject,revoke'], 'note' => ['nullable', 'string', 'max:300'], 'valid_to' => ['nullable', 'date', 'after:today'], 'scopes' => ['nullable', 'array']]);
        match ($d['decision']) {
            'approve' => $this->proxies->approve($row, $request->user(), $d['scopes'] ?? null, $d['valid_to'] ?? null, $d['note'] ?? null),
            'reject' => $this->proxies->reject($row, $request->user(), (string) ($d['note'] ?? '')),
            default => $this->proxies->revoke($row, (string) ($d['note'] ?: 'ended by staff'), $request->user()),
        };

        return back()->with('success', __('Saved.'));
    }

    public function outbox(Request $request)
    {
        $this->allow($request, 'portal.communication.view');
        $rows = PortalOutbox::query()->where('organization_id', $this->organizationId())->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->when($request->query('purpose'), fn ($q, $s) => $q->where('purpose', $s))->latest('id')->paginate(30)->withQueryString();

        return view('admin.portal.outbox', ['rows' => $rows]);
    }

    public function accessLog(Request $request)
    {
        $this->allow($request, 'portal.document.view');
        $rows = PortalAccessLog::query()->with(['account:id,phone', 'patient:id,first_name,last_name,enterprise_patient_no'])->where('organization_id', $this->organizationId())
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))->when($request->boolean('proxy'), fn ($q) => $q->where('as_proxy', true))->latest('id')->paginate(40)->withQueryString();

        return view('admin.portal.access-log', ['rows' => $rows, 'links' => PortalDocument::query()->where('organization_id', $this->organizationId())->latest('id')->limit(15)->get()]);
    }

    public function revokeLink(Request $request, string $document)
    {
        $this->allow($request, 'portal.account.manage');
        $this->find(PortalDocument::class, $document)->update(['revoked_at' => now()]);

        return back()->with('success', __('The link no longer works.'));
    }

    public function settings(Request $request)
    {
        $this->allow($request, 'portal.settings.manage');
        $p = app(PortalPolicy::class);

        return view('admin.portal.settings', ['v' => [
            'release_default_mode' => $p->get('release.default_mode'), 'release_default_delay_hours' => $p->get('release.default_delay_hours'), 'quiet_start' => ((array) $p->get('communication.quiet_hours'))['start'] ?? '21:00', 'quiet_end' => ((array) $p->get('communication.quiet_hours'))['end'] ?? '08:00',
            'max_marketing_per_week' => $p->get('communication.max_marketing_per_week'), 'link_ttl_days' => $p->get('documents.link_ttl_days'), 'change_cutoff_hours' => $p->get('appointments.change_cutoff_hours'), 'min_lead_hours' => $p->get('appointments.min_lead_hours'),
            'max_failures' => $p->get('login.max_failures'), 'lock_minutes' => $p->get('login.lock_minutes'), 'terms_version' => $p->get('terms_version'),
        ]]);
    }

    public function saveSettings(Request $request)
    {
        $this->allow($request, 'portal.settings.manage');
        $d = $request->validate(['release_default_mode' => ['required', 'in:immediate,after_review,delayed,never'], 'release_default_delay_hours' => ['required', 'integer', 'between:0,720'], 'quiet_start' => ['required', 'date_format:H:i'], 'quiet_end' => ['required', 'date_format:H:i'],
            'max_marketing_per_week' => ['required', 'integer', 'between:0,14'], 'link_ttl_days' => ['required', 'integer', 'between:1,60'], 'change_cutoff_hours' => ['required', 'integer', 'between:0,168'], 'min_lead_hours' => ['required', 'integer', 'between:0,72'],
            'max_failures' => ['required', 'integer', 'between:3,20'], 'lock_minutes' => ['required', 'integer', 'between:1,1440'], 'terms_version' => ['required', 'string', 'max:20']]);
        $this->settings->set('portal.release.default_mode', $d['release_default_mode'], 'portal');
        $this->settings->set('portal.release.default_delay_hours', (int) $d['release_default_delay_hours'], 'portal');
        $this->settings->set('portal.communication.quiet_hours', ['start' => $d['quiet_start'], 'end' => $d['quiet_end']], 'portal');
        $this->settings->set('portal.communication.max_marketing_per_week', (int) $d['max_marketing_per_week'], 'portal');
        $this->settings->set('portal.documents.link_ttl_days', (int) $d['link_ttl_days'], 'portal');
        $this->settings->set('portal.appointments.change_cutoff_hours', (int) $d['change_cutoff_hours'], 'portal');
        $this->settings->set('portal.appointments.min_lead_hours', (int) $d['min_lead_hours'], 'portal');
        $this->settings->set('portal.login.max_failures', (int) $d['max_failures'], 'portal');
        $this->settings->set('portal.login.lock_minutes', (int) $d['lock_minutes'], 'portal');
        $this->settings->set('portal.terms_version', $d['terms_version'], 'portal');
        $this->audit->log('UPDATE', Setting::class, null, null, $d);

        return back()->with('success', __('Saved.'));
    }
}
