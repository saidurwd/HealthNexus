<?php

namespace Modules\Interop\Http\Controllers\Web;

use App\Models\Integration\IntegrationCredential;
use App\Models\Integration\IntegrationEndpoint;
use App\Models\Integration\IntegrationIdMap;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Models\Integration\IntegrationRoute;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Interop\CredentialService;
use App\Services\Interop\EventPublisher;
use App\Services\Interop\ExchangeGuard;
use App\Services\Interop\IdMapper;
use App\Services\Interop\InteropException;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Outside systems: who they are, how we sign in to them and they to us, where we send, what we send and when (S01, S03, S04). */
class InteropPartnerController extends InteropStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly CredentialService $credentials, private readonly EventPublisher $publisher, private readonly IdMapper $ids, private readonly AuditLogger $audit)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'interoperability.partner.view');
        $rows = IntegrationPartner::query()->where('organization_id', $this->organizationId())->orderBy('name')->get();
        $stats = IntegrationMessage::query()->where('organization_id', $this->organizationId())->where('created_at', '>=', now()->subDays(7))->selectRaw('partner_id, status, count(*) c')->groupBy('partner_id', 'status')->get()->groupBy('partner_id');

        return view('admin.interop.partners', ['rows' => $rows, 'stats' => $stats, 'kinds' => config('interop.partner_kinds'), 'profiles' => array_keys((array) config('interop.profiles'))]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'interoperability.partner.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'regex:/^[A-Za-z0-9_-]{2,40}$/'], 'kind' => ['required', Rule::in(config('interop.partner_kinds'))], 'direction' => ['required', 'in:in,out,both'],
            'profile' => ['required', Rule::in(array_keys((array) config('interop.profiles')))], 'consent_type' => ['nullable', 'string', 'max:40'], 'requires_consent' => ['nullable', 'boolean'], 'sending_application' => ['nullable', 'string', 'max:60'], 'sending_facility' => ['nullable', 'string', 'max:60'],
            'ae_title' => ['nullable', 'string', 'max:16'], 'contact' => ['nullable', 'string', 'max:200']]);
        if (IntegrationPartner::query()->where(['organization_id' => $this->organizationId(), 'code' => $d['code']])->exists()) {
            return back()->withInput()->withErrors(['code' => __('That code is already used.')]);
        }
        $p = IntegrationPartner::create(['organization_id' => $this->organizationId(), 'name' => $d['name'], 'code' => $d['code'], 'kind' => $d['kind'], 'direction' => $d['direction'], 'status' => 'draft', 'profile' => $d['profile'],
            'requires_consent' => (bool) ($d['requires_consent'] ?? true), 'consent_type' => ($d['consent_type'] ?? null) ?: 'data_sharing', 'ae_title' => $d['ae_title'] ?? null, 'contact' => $d['contact'] ?? null, 'created_by' => $request->user()->id,
            'settings' => array_filter(['sending_application' => $d['sending_application'] ?? null, 'sending_facility' => $d['sending_facility'] ?? null])]);
        $this->audit->log('CREATE', IntegrationPartner::class, $p->id, null, ['code' => $p->code, 'kind' => $p->kind]);

        return redirect()->route('admin.interop.partners.show', $p->public_id)->with('success', __('Saved. It stays in draft until you switch it on.'));
    }

    public function show(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.partner.view');
        $p = $this->find(IntegrationPartner::class, $partner);

        return view('admin.interop.partner', ['p' => $p, 'credentials' => $p->credentials()->orderBy('label')->orderByDesc('version')->get(), 'endpoints' => $p->endpoints()->get(), 'tokens' => $p->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at']),
            'routes' => IntegrationRoute::query()->where('partner_id', $p->id)->orderBy('event_key')->get(), 'events' => array_keys((array) config('interop.events')), 'maps' => IntegrationIdMap::query()->where(['organization_id' => $p->organization_id, 'scope' => $p->code])->latest('id')->limit(15)->get(),
            'recent' => IntegrationMessage::query()->where('partner_id', $p->id)->latest('id')->limit(10)->get(), 'secret' => session('interop_secret'), 'token' => session('interop_token'), 'webhookUrl' => url('/api/v1/interoperability/webhooks/'.$p->public_id)]);
    }

    public function update(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.partner.manage');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['status' => ['nullable', 'in:draft,active,paused,retired'], 'name' => ['nullable', 'string', 'max:150'], 'profile' => ['nullable', Rule::in(array_keys((array) config('interop.profiles')))], 'requires_consent' => ['nullable', 'boolean'],
            'consent_type' => ['nullable', 'string', 'max:40'], 'contact' => ['nullable', 'string', 'max:200']]);
        if (array_key_exists('requires_consent', $d) && $d['requires_consent'] !== null) {
            $d['requires_consent'] = (bool) $d['requires_consent'];
        }
        $p->update(array_filter($d, fn ($v) => $v !== null));
        if (($d['status'] ?? null) === 'retired') {
            $this->credentials->revokeTokens($p);
        }
        $this->audit->log('UPDATE', IntegrationPartner::class, $p->id, null, $d);

        return back()->with('success', __('Saved.'));
    }

    public function credential(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.credential.manage');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['action' => ['required', 'in:create,rotate,revoke'], 'label' => ['required', 'string', 'max:40'], 'kind' => ['nullable', 'in:api_key,bearer,basic,hmac_secret,client_cert'], 'secret' => ['nullable', 'string', 'max:500'], 'overlap_hours' => ['nullable', 'integer', 'between:0,720'], 'credential_id' => ['nullable', 'integer']]);
        try {
            if ($d['action'] === 'create') {
                [, $plain] = $this->credentials->create($p, $d['label'], $d['kind'] ?? 'bearer', $d['secret'] ?? null, $request->user());
            } elseif ($d['action'] === 'rotate') {
                [, $plain] = $this->credentials->rotate($p, $d['label'], $request->user(), isset($d['overlap_hours']) ? (int) $d['overlap_hours'] : null, $d['secret'] ?? null);
            } else {
                $this->credentials->revoke(IntegrationCredential::query()->where('partner_id', $p->id)->findOrFail($d['credential_id'] ?? 0), $request->user());
                $plain = null;
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['label' => $e instanceof ModelNotFoundException ? __('There is no active secret with that label to rotate.') : __('That secret already exists.')]);
        }

        return back()->with('success', __('Saved.'))->with('interop_secret', $plain);
    }

    public function token(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.credential.manage');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['action' => ['required', 'in:issue,revoke'], 'abilities' => ['nullable', 'array'], 'abilities.*' => ['string']]);
        if ($d['action'] === 'revoke') {
            $this->credentials->revokeTokens($p);

            return back()->with('success', __('Every token of this partner was revoked.'));
        }

        return back()->with('success', __('Token made. Copy it now: it is shown only once.'))->with('interop_token', $this->credentials->issueToken($p, $d['abilities'] ?? ['fhir.read']));
    }

    public function endpoint(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.partner.manage');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['name' => ['required', 'string', 'max:80'], 'transport' => ['required', 'in:https,mllp,dicomweb,national'], 'url' => ['required', 'string', 'max:300'], 'format' => ['required', 'in:fhir,hl7,json'], 'timeout_seconds' => ['nullable', 'integer', 'between:1,120']]);
        if ($d['transport'] === 'https' && ! preg_match('#^https://#', $d['url'])) {
            return back()->withInput()->withErrors(['url' => __('An https address is needed.')]);
        }
        IntegrationEndpoint::create(['partner_id' => $p->id, 'name' => $d['name'], 'transport' => $d['transport'], 'url' => $d['url'], 'format' => $d['format'], 'timeout_seconds' => $d['timeout_seconds'] ?? 10, 'active' => true]);

        return back()->with('success', __('Saved.'));
    }

    public function toggleEndpoint(Request $request, string $partner, int $endpoint)
    {
        $this->allow($request, 'interoperability.partner.manage');
        $e = IntegrationEndpoint::query()->where('partner_id', $this->find(IntegrationPartner::class, $partner)->id)->findOrFail($endpoint);
        $e->update(['active' => ! $e->active]);

        return back()->with('success', __('Saved.'));
    }

    public function route(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.route.manage');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['action' => ['required', 'in:create,toggle,delete'], 'route_id' => ['nullable', 'integer'], 'event_key' => ['nullable', Rule::in(array_keys((array) config('interop.events')))], 'format' => ['nullable', 'in:fhir,hl7,json'], 'endpoint_id' => ['nullable', 'integer'],
            'condition_key' => ['nullable', 'string', 'max:40'], 'condition_value' => ['nullable', 'string', 'max:80'], 'priority' => ['nullable', 'integer', 'between:1,999']]);
        if ($d['action'] === 'create') {
            if (empty($d['event_key']) || empty($d['format'])) {
                return back()->withErrors(['event_key' => __('Choose the event and the format.')]);
            }
            IntegrationRoute::create(['organization_id' => $this->organizationId(), 'name' => $d['event_key'].' → '.$p->code, 'event_key' => $d['event_key'], 'partner_id' => $p->id, 'format' => $d['format'], 'endpoint_id' => $p->endpoints()->whereKey($d['endpoint_id'] ?? 0)->value('id'),
                'conditions' => ! empty($d['condition_key']) ? [$d['condition_key'] => $d['condition_value'] ?? ''] : null, 'priority' => $d['priority'] ?? 100, 'active' => true]);
        } else {
            $r = IntegrationRoute::query()->where('partner_id', $p->id)->findOrFail($d['route_id'] ?? 0);
            $d['action'] === 'delete' ? $r->delete() : $r->update(['active' => ! $r->active]);
        }

        return back()->with('success', __('Saved.'));
    }

    public function testSend(Request $request, string $partner)
    {
        $this->allow($request, 'interoperability.message.send_test');
        $p = $this->find(IntegrationPartner::class, $partner);
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:60'], 'format' => ['required', 'in:fhir,hl7,json']]);
        $patient = Patient::query()->where(['organization_id' => $this->organizationId(), 'enterprise_patient_no' => trim($d['patient_no'])])->first();
        if ($patient === null) {
            return back()->withErrors(['patient_no' => __('No patient has that number.')]);
        }
        $guard = app(ExchangeGuard::class);
        if (! $guard->check($p, $patient, 'Patient', 'send', null, $request->user())) {
            return back()->withErrors(['patient_no' => __('This patient has not agreed to share with this partner.')]);
        }
        try {
            $this->publisher->sendNow($p, $d['format'], $patient, 'manual');
        } catch (InteropException $e) {
            return back()->withErrors(['format' => $e->getMessage()]);
        }

        return back()->with('success', __('Queued. The sender will deliver it within a minute.'));
    }

    public function idmaps(Request $request)
    {
        $this->allow($request, 'interoperability.idmap.view');
        $rows = IntegrationIdMap::query()->where('organization_id', $this->organizationId())->when($request->query('scope'), fn ($q, $s) => $q->where('scope', $s))->when($request->query('q'), fn ($q, $v) => $q->where('external_id', 'like', '%'.$v.'%'))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.interop.idmaps', ['rows' => $rows, 'scopes' => IntegrationIdMap::query()->where('organization_id', $this->organizationId())->distinct()->pluck('scope'), 'partners' => IntegrationPartner::query()->where('organization_id', $this->organizationId())->orderBy('code')->get(['code', 'name'])]);
    }

    public function link(Request $request)
    {
        $this->allow($request, 'interoperability.idmap.manage');
        $d = $request->validate(['partner' => ['required', 'string'], 'patient_no' => ['required', 'string', 'max:60'], 'external_id' => ['required', 'string', 'max:120']]);
        $partner = IntegrationPartner::query()->where(['organization_id' => $this->organizationId(), 'code' => $d['partner']])->firstOrFail();
        $patient = Patient::query()->where(['organization_id' => $this->organizationId(), 'enterprise_patient_no' => trim($d['patient_no'])])->first();
        if ($patient === null) {
            return back()->withErrors(['patient_no' => __('No patient has that number.')]);
        }
        $clash = IntegrationIdMap::query()->where(['organization_id' => $this->organizationId(), 'scope' => $partner->code, 'entity_type' => 'Patient', 'external_id' => $d['external_id']])->where('local_id', '!=', $patient->id)->exists();
        if ($clash) {
            return back()->withErrors(['external_id' => __('That partner number already belongs to another patient.')]);
        }
        $this->ids->link($partner->code, 'Patient', $patient->id, $d['external_id'], $this->organizationId());
        $this->audit->log('CREATE', IntegrationIdMap::class, null, null, ['partner' => $partner->code, 'patient_id' => $patient->id, 'by' => $request->user()->id]);

        return back()->with('success', __('Saved.'));
    }
}
