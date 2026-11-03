<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Billing\BillingReceipt;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Portal\PortalDevice;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalResultRelease;
use App\Models\Prescription;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\ConsentService;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalAudit;
use App\Services\Portal\PortalAuthService;
use App\Services\Portal\PortalExportService;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalRecordsService;
use App\Services\Portal\PortalRetentionService;
use App\Services\Portal\ProxyService;
use App\Services\Portal\RefillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Home, records, results, prescriptions, family, settings (S03 to S05, S07, S08, S10). */
class PortalWebHomeController extends PortalWebController
{
    public function __construct(PortalAccess $access, private readonly PortalAppointmentService $appointments, private readonly PortalPaymentService $payments, private readonly PortalRecordsService $records,
        private readonly RefillService $refills, private readonly DocumentShareService $documents, private readonly PortalAudit $audit, private readonly CommunicationService $comms,
        private readonly ConsentService $consents, private readonly ProxyService $proxies, private readonly PortalAuthService $auth,
        private readonly PortalExportService $export, private readonly PortalRetentionService $retention)
    {
        parent::__construct($access);
    }

    public function home(Request $request)
    {
        $account = $this->account($request);
        $ids = $this->ids($request, 'appointments');
        $next = $this->appointments->upcoming($ids)->first();

        return view('portal.home', [
            'subjects' => $this->access->subjects($account), 'for' => $request->session()->get('portal_for'), 'next' => $next, 'queue' => $next ? $this->appointments->queue($next) : null,
            'unread' => PortalNotification::query()->where('patient_id', $account->patient_id)->whereNull('read_at')->count(),
            'billsDue' => $this->payments->invoices($this->ids($request, 'billing'), true)->count(),
            'newResults' => PortalResultRelease::query()->whereIn('patient_id', $this->ids($request, 'results'))->where('status', 'released')->where('released_at', '>=', now()->subDays(7))->count(),
            'refills' => PortalRefillRequest::query()->whereIn('patient_id', $this->ids($request, 'prescriptions'))->where('status', 'requested')->count(),
        ]);
    }

    public function switch(Request $request)
    {
        $d = $request->validate(['for' => ['nullable', 'string', 'max:26']]);
        $for = $d['for'] ?? null;
        if ($for) {
            $this->access->patientFor($this->account($request), $for, 'appointments'); // must be someone this login may open
        }
        $for ? $request->session()->put('portal_for', $for) : $request->session()->forget('portal_for');

        return redirect()->route('portal.home');
    }

    public function notifications(Request $request)
    {
        $account = $this->account($request);
        $rows = PortalNotification::query()->where('patient_id', $account->patient_id)->latest('id')->paginate(20);
        PortalNotification::query()->where('patient_id', $account->patient_id)->whereNull('read_at')->update(['read_at' => now()]);

        return view('portal.notifications', ['rows' => $rows]);
    }

    public function records(Request $request)
    {
        $patient = $this->subject($request, 'results');
        $this->audit->access($this->account($request), $patient->id, 'view_record');

        return view('portal.records', ['p' => $patient, 's' => $this->records->summary($patient), 'visits' => $this->records->visits($patient->id, 30), 'stays' => $this->records->stays($patient->id), 'insurance' => $this->records->insurance($patient->id)]);
    }

    public function results(Request $request)
    {
        $ids = $this->ids($request, 'results');

        return view('portal.results', ['rows' => $this->records->results($ids), 'waiting' => $this->records->pendingNotice($ids)]);
    }

    public function result(Request $request, string $result)
    {
        $row = $this->records->findReleased($this->ids($request, 'results'), $result);
        $this->audit->access($this->account($request), $row->patient_id, 'view_result', $row);

        return view('portal.result', ['r' => $row, 'v' => $this->records->view($row)]);
    }

    public function prescriptions(Request $request)
    {
        $ids = $this->ids($request, 'prescriptions');

        return view('portal.prescriptions', ['allergies' => PatientAllergy::query()->whereIn('patient_id', $ids)->where('is_active', true)->orderBy('substance')->get(['substance', 'severity']), 'rows' => $this->records->prescriptions($ids), 'requests' => PortalRefillRequest::query()->whereIn('patient_id', $ids)->latest('id')->limit(10)->get()->keyBy('prescription_id')]);
    }

    public function refill(Request $request, string $prescription)
    {
        $d = $request->validate(['note' => ['nullable', 'string', 'max:300'], 'key' => ['required', 'string', 'max:64']]);
        $rx = Prescription::query()->whereIn('patient_id', $this->ids($request, 'prescriptions'))->where('public_id', $prescription)->firstOrFail();
        $this->refills->request($this->account($request), Patient::query()->findOrFail($rx->patient_id), $rx, $d['note'] ?? null, $d['key']);

        return back()->with('success', __('Your request was sent to the pharmacy team. A person will review it.'));
    }

    public function share(Request $request)
    {
        $d = $request->validate(['type' => ['required', 'in:lab_report,radiology_report,receipt,prescription'], 'id' => ['required', 'string']]);
        $patient = $this->subject($request, 'documents');
        $sourceId = match ($d['type']) {
            'lab_report', 'radiology_report' => (int) PortalResultRelease::query()->where(['patient_id' => $patient->id, 'public_id' => $d['id']])->firstOrFail()->source_id,
            'receipt' => (int) BillingReceipt::query()->where('public_id', $d['id'])->firstOrFail()->id,
            'prescription' => (int) Prescription::query()->where(['patient_id' => $patient->id, 'public_id' => $d['id']])->firstOrFail()->id,
            default => abort(404),
        };
        [, $plain] = $this->documents->share($patient->id, $d['type'], $sourceId, $this->account($request));

        return redirect()->route('portal.documents.open', $plain)->with('success', __('This link stops working after :d days.', ['d' => config('portal.documents.link_ttl_days')]));
    }

    public function document(Request $request, string $token)
    {
        [$doc, $content] = $this->documents->open($this->account($request), $token, $this->access);

        return view('portal.document', ['doc' => $doc, 'c' => $content]);
    }

    public function family(Request $request)
    {
        $account = $this->account($request);

        return view('portal.family', [
            'acting' => PortalProxy::query()->with('patient:id,public_id,first_name,last_name')->where('account_id', $account->id)->whereIn('status', ['requested', 'active'])->get(),
            'withAccess' => PortalProxy::query()->where('patient_id', $account->patient_id)->where('status', 'active')->get(),
            'scopes' => config('portal.proxy.scopes'), 'relationships' => config('portal.proxy.relationships'),
        ]);
    }

    public function requestProxy(Request $request)
    {
        $d = $request->validate(['identifier' => ['required', 'string', 'max:60'], 'date_of_birth' => ['required', 'date'], 'relationship' => ['required', 'string', 'max:14'], 'scopes' => ['required', 'array'], 'scopes.*' => ['string'], 'evidence' => ['nullable', 'string', 'max:300']]);
        $this->proxies->request($this->account($request), $d['identifier'], $d['date_of_birth'], $d['relationship'], $d['scopes'], $d['evidence'] ?? null);

        return back()->with('success', __('Your request was received. The hospital will check it and tell you the result.'));
    }

    public function endProxy(Request $request, string $proxy)
    {
        $account = $this->account($request);
        $row = PortalProxy::query()->where('public_id', $proxy)->where(fn ($q) => $q->where('account_id', $account->id)->orWhere('patient_id', $account->patient_id))->firstOrFail();
        $this->proxies->revoke($row, $row->account_id === $account->id ? 'ended by the relative' : 'ended by the patient');
        $request->session()->forget('portal_for');

        return back()->with('success', __('Access ended.'));
    }

    public function settings(Request $request)
    {
        $account = $this->account($request);
        $patient = $account->patient;

        return view('portal.settings', ['a' => $account, 'matrix' => $this->comms->matrix($patient), 'consents' => $this->consents->list($patient), 'devices' => $account->devices()->whereNull('revoked_at')->orderByDesc('last_seen_at')->get(),
            'channels' => config('portal.communication.channels'), 'purposes' => config('portal.communication.purposes')]);
    }

    public function savePreferences(Request $request)
    {
        $account = $this->account($request);
        $patient = $account->patient;
        $given = (array) $request->input('pref', []);
        foreach ((array) config('portal.communication.channels') as $channel) {
            foreach (['service', 'marketing'] as $purpose) {
                $this->comms->setPreference($patient, $channel, $purpose, ! empty($given[$channel][$purpose]));
            }
        }
        $account->update(['mfa_required' => $request->boolean('mfa')]);
        foreach ((array) config('portal.consent_types') as $type) {
            $this->consents->set($account, $patient, $type, ! empty($request->input('consent', [])[$type]));
        }

        return back()->with('success', __('Saved.'));
    }

    public function changePassword(Request $request)
    {
        $d = $request->validate(['current' => ['required', 'string'], 'password' => ['required', 'string', 'max:100', 'confirmed']]);
        $this->auth->changePassword($this->account($request), $d['current'], $d['password']);

        return back()->with('success', __('Your password was changed.'));
    }

    public function signOutDevice(Request $request, int $device)
    {
        $account = $this->account($request);
        $this->auth->revokeDevice($account, PortalDevice::query()->where('account_id', $account->id)->findOrFail($device));

        return back()->with('success', __('That device was signed out.'));
    }

    public function help()
    {
        return view('portal.help', ['n' => config('portal.messaging.emergency_number')]);
    }

    public function export(Request $request)
    {
        $name = 'my-health-data-'.now()->format('Ymd').'.json';

        return response()->streamDownload(fn () => print json_encode($this->export->build($this->account($request)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $name, ['Content-Type' => 'application/json']);
    }

    public function close(Request $request)
    {
        $d = $request->validate(['password' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:200']]);
        $this->retention->closeOwn($this->account($request), $d['password'], $d['reason'] ?? null);
        Auth::guard('portal')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('success', __('Your login was closed. Your hospital record is not affected.'));
    }
}
