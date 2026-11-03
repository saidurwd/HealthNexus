<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalProxy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Portal\PortalThread;
use App\Models\Prescription;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\ConsentService;
use App\Services\Portal\PhoneNumber;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalExportService;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalPresenter;
use App\Services\Portal\PortalRetentionService;
use App\Services\Portal\PortalTelehealthService;
use App\Services\Portal\ProxyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Who am I, what is new, how may you contact me, who may act for me (S03, S08, S10). */
class PortalAccountApiController extends PortalApiController
{
    public function __construct(PortalAccess $access, private readonly CommunicationService $comms, private readonly ConsentService $consents,
        private readonly ProxyService $proxies, private readonly PortalAppointmentService $appointments, private readonly PortalPaymentService $payments,
        private readonly PortalExportService $export, private readonly PortalRetentionService $retention, private readonly PortalTelehealthService $telehealth)
    {
        parent::__construct($access);
    }

    public function me(Request $request): JsonResponse
    {
        $account = $this->account($request);

        return $this->ok(['language' => $account->language, 'phone_masked' => PhoneNumber::mask($account->phone),
            'people' => $this->access->subjects($account)->map(fn ($s) => PortalPresenter::patient($s['patient']) + ['relation' => $s['relation'], 'scopes' => $s['scopes']])->values()->all()]);
    }

    public function home(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $ids = $this->access->patientIds($account, 'appointments');
        $billing = $this->access->patientIds($account, 'billing');

        return $this->ok([
            'next_appointment' => ($a = $this->appointments->upcoming($ids)->first()) ? PortalPresenter::appointment($a) + ['queue' => $this->appointments->queue($a)] : null,
            'unread_notifications' => PortalNotification::query()->where('patient_id', $account->patient_id)->whereNull('read_at')->count(),
            'bills_due' => $this->payments->invoices($billing, true)->count(),
            'new_results' => PortalResultRelease::query()->whereIn('patient_id', $this->access->patientIds($account, 'results'))->where('status', 'released')->where('released_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $rows = PortalNotification::query()->whereIn('patient_id', $this->ids($request, 'preferences'))->latest('id')->paginate($this->page($request));

        return $this->ok($rows->getCollection()->map(fn ($n) => PortalPresenter::notification($n))->values()->all(), 'OK', ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()]);
    }

    public function readNotifications(Request $request): JsonResponse
    {
        PortalNotification::query()->whereIn('patient_id', $this->ids($request, 'preferences'))->whereNull('read_at')->update(['read_at' => now()]);

        return $this->ok(null, __('Marked as read.'));
    }

    public function preferences(Request $request): JsonResponse
    {
        return $this->ok($this->comms->matrix($this->subject($request, 'preferences')), 'OK', ['note' => __('Messages about your own care and security cannot be switched off.')]);
    }

    public function setPreference(Request $request): JsonResponse
    {
        $d = $request->validate(['channel' => ['required', 'string', 'max:8'], 'purpose' => ['required', 'string', 'max:14'], 'opted_in' => ['required', 'boolean']]);
        $this->comms->setPreference($this->subject($request, 'preferences'), $d['channel'], $d['purpose'], (bool) $d['opted_in']);

        return $this->ok($this->comms->matrix($this->subject($request, 'preferences')), __('Saved.'));
    }

    public function consents(Request $request): JsonResponse
    {
        return $this->ok($this->consents->list($this->subject($request, 'consents'))->all());
    }

    public function setConsent(Request $request): JsonResponse
    {
        $d = $request->validate(['type' => ['required', 'string', 'max:40'], 'granted' => ['required', 'boolean']]);
        $this->consents->set($this->account($request), $this->subject($request, 'consents'), $d['type'], (bool) $d['granted']);

        return $this->ok($this->consents->list($this->subject($request, 'consents'))->all(), __('Saved.'));
    }

    public function family(Request $request): JsonResponse
    {
        $account = $this->account($request);

        return $this->ok([
            'acting_for' => PortalProxy::query()->with('patient:id,public_id,first_name,last_name')->where('account_id', $account->id)->whereIn('status', ['requested', 'active'])->get()->map(fn ($p) => ['id' => $p->public_id, 'person' => trim($p->patient->first_name.' '.$p->patient->last_name), 'relationship' => $p->relationship, 'status' => $p->status, 'scopes' => $p->scopes, 'until' => $p->valid_to?->toDateString()])->all(),
            'people_with_access' => PortalProxy::query()->where('patient_id', $account->patient_id)->where('status', 'active')->get()->map(fn ($p) => ['id' => $p->public_id, 'relationship' => $p->relationship, 'scopes' => $p->scopes, 'until' => $p->valid_to?->toDateString()])->all(),
        ]);
    }

    /** Always answers the same way; staff see whether it matched. */
    public function requestProxy(Request $request): JsonResponse
    {
        $d = $request->validate(['identifier' => ['required', 'string', 'max:60'], 'date_of_birth' => ['required', 'date'], 'relationship' => ['required', 'string', 'max:14'], 'scopes' => ['required', 'array'], 'scopes.*' => ['string'], 'evidence' => ['nullable', 'string', 'max:300']]);
        $this->proxies->request($this->account($request), $d['identifier'], $d['date_of_birth'], $d['relationship'], $d['scopes'], $d['evidence'] ?? null);

        return $this->ok(null, __('Your request was received. The hospital will check it and tell you the result.'), [], 202);
    }

    public function endProxy(Request $request, string $proxy): JsonResponse
    {
        $account = $this->account($request);
        $row = PortalProxy::query()->where('public_id', $proxy)->where(fn ($q) => $q->where('account_id', $account->id)->orWhere('patient_id', $account->patient_id))->firstOrFail();
        $this->proxies->revoke($row, $row->account_id === $account->id ? 'ended by the relative' : 'ended by the patient');

        return $this->ok(null, __('Access ended.'));
    }

    /** Everything the portal holds about the signed-in person (S12). Own record only. */
    public function export(Request $request): JsonResponse
    {
        return $this->ok($this->export->build($this->account($request)));
    }

    public function close(Request $request): JsonResponse
    {
        $d = $request->validate(['password' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:200']]);
        $this->retention->closeOwn($this->account($request), $d['password'], $d['reason'] ?? null);

        return $this->ok(null, __('Your login was closed. Your hospital record is not affected.'));
    }

    /** What changed since a time, for an app that keeps a copy on the phone (offline support, S12). Cheap to call: ids and times only; fetch the rest with the normal endpoints. */
    public function sync(Request $request): JsonResponse
    {
        $d = $request->validate(['since' => ['required', 'date']]);
        $since = Carbon::parse($d['since']);
        $account = $this->account($request);

        return $this->ok([
            'server_time' => now()->toIso8601String(),
            'appointments' => Appointment::query()->whereIn('patient_id', $this->access->patientIds($account, 'appointments'))->where('updated_at', '>=', $since)->pluck('public_id')->all(),
            'results' => PortalResultRelease::query()->whereIn('patient_id', $this->access->patientIds($account, 'results'))->where('status', 'released')->where('released_at', '>=', $since)->pluck('public_id')->all(),
            'prescriptions' => Prescription::query()->whereIn('patient_id', $this->access->patientIds($account, 'prescriptions'))->where('updated_at', '>=', $since)->pluck('public_id')->all(),
            'invoices' => BillingInvoice::query()->whereIn('patient_id', $this->access->patientIds($account, 'billing'))->where('updated_at', '>=', $since)->pluck('public_id')->all(),
            'threads' => PortalThread::query()->whereIn('patient_id', $this->access->patientIds($account, 'messages'))->where('updated_at', '>=', $since)->pluck('public_id')->all(),
            'notifications' => PortalNotification::query()->where('patient_id', $account->patient_id)->where('created_at', '>=', $since)->count(),
        ]);
    }

    public function video(Request $request, string $appointment): JsonResponse
    {
        $row = Appointment::query()->whereIn('patient_id', $this->access->patientIds($this->account($request), 'appointments'))->where('public_id', $appointment)->firstOrFail();

        return $this->ok($this->telehealth->joinInfo($row));
    }
}
