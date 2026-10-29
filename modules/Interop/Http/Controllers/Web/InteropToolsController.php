<?php

namespace Modules\Interop\Http\Controllers\Web;

use App\Models\Integration\DicomWorklistEntry;
use App\Models\Integration\IntegrationPartner;
use App\Models\Patient;
use App\Models\Terminology\CodeSystem;
use App\Services\Interop\DicomWorklistService;
use App\Services\Interop\InteropException;
use App\Services\Interop\InteropPolicy;
use App\Services\Interop\NationalExchangeService;
use App\Services\Interop\TerminologyService;
use App\Services\SettingsService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Scanner worklist, the national exchange, the code registry as seen from outside, and the settings (S09, S04, S11). */
class InteropToolsController extends InteropStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly DicomWorklistService $worklist, private readonly NationalExchangeService $national, private readonly TerminologyService $terminology, private readonly SettingsService $settings)
    {
        parent::__construct($tenant);
    }

    public function worklist(Request $request)
    {
        $this->allow($request, 'interoperability.dicom.worklist.view');
        $status = $request->query('status', 'scheduled');
        $rows = DicomWorklistEntry::query()->where('organization_id', $this->organizationId())->when($status !== 'all', fn ($q) => $q->where('status', $status))->orderBy('scheduled_at')->paginate(30)->withQueryString();

        return view('admin.interop.worklist', ['rows' => $rows, 'status' => $status]);
    }

    public function syncWorklist(Request $request)
    {
        $this->allow($request, 'interoperability.dicom.worklist.manage');
        $r = $this->worklist->sync($this->organizationId());

        return back()->with('success', __(':c new, :u changed.', ['c' => $r['created'], 'u' => $r['updated']]));
    }

    public function national(Request $request)
    {
        $this->allow($request, 'interoperability.national.publish');

        return view('admin.interop.national', ['exchange' => IntegrationPartner::query()->where(['organization_id' => $this->organizationId(), 'kind' => 'national_exchange'])->first(), 'driver' => config('interop.national.driver')]);
    }

    public function share(Request $request)
    {
        $this->allow($request, 'interoperability.national.publish');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:60'], 'override_reason' => ['nullable', 'string', 'max:200']]);
        $patient = Patient::query()->where(['organization_id' => $this->organizationId(), 'enterprise_patient_no' => trim($d['patient_no'])])->first();
        $exchange = IntegrationPartner::query()->where(['organization_id' => $this->organizationId(), 'kind' => 'national_exchange'])->first();
        if ($patient === null || $exchange === null) {
            return back()->withErrors(['patient_no' => $patient === null ? __('No patient has that number.') : __('The national exchange has not been set up as a partner yet.')]);
        }
        try {
            $this->national->share($exchange, $patient, $request->user(), $d['override_reason'] ?? null);
        } catch (InteropException $e) {
            return back()->withInput()->withErrors(['patient_no' => $e->getMessage()]);
        }

        return back()->with('success', __('Queued for the national exchange.'));
    }

    public function terminology(Request $request)
    {
        $this->allow($request, 'interoperability.terminology.view');
        $found = null;
        $maps = [];
        if ($request->query('system') && $request->query('code')) {
            $found = $this->terminology->lookup((string) $request->query('system'), (string) $request->query('code'));
            $maps = $this->terminology->translate((string) $request->query('system'), (string) $request->query('code'));
        }

        return view('admin.interop.terminology', ['systems' => CodeSystem::query()->orderBy('key')->get(['key', 'uri', 'name']), 'found' => $found, 'maps' => $maps]);
    }

    public function settings(Request $request)
    {
        $this->allow($request, 'interoperability.settings.manage');
        $p = app(InteropPolicy::class);

        return view('admin.interop.settings', ['v' => ['max_attempts' => $p->get('delivery.max_attempts'), 'backoff' => implode(', ', (array) $p->get('delivery.backoff_seconds')), 'overlap_hours' => $p->get('credentials.overlap_hours'), 'token_days' => $p->get('tokens.ttl_days'),
            'processed_days' => $p->get('retention.processed_payload_days'), 'dead_days' => $p->get('retention.dead_payload_days'), 'access_days' => $p->get('retention.access_log_days'), 'page_size' => $p->get('fhir.page_size'), 'tolerance' => $p->get('webhook.tolerance_seconds')]]);
    }

    public function saveSettings(Request $request)
    {
        $this->allow($request, 'interoperability.settings.manage');
        $d = $request->validate(['max_attempts' => ['required', 'integer', 'between:1,20'], 'backoff' => ['required', 'regex:/^\d+(\s*,\s*\d+)*$/'], 'overlap_hours' => ['required', 'integer', 'between:0,720'], 'token_days' => ['required', 'integer', 'between:1,730'],
            'processed_days' => ['required', 'integer', 'between:1,3650'], 'dead_days' => ['required', 'integer', 'between:1,3650'], 'access_days' => ['required', 'integer', 'between:30,3650'], 'page_size' => ['required', 'integer', 'between:5,100'], 'tolerance' => ['required', 'integer', 'between:30,3600']]);
        $this->settings->set('interop.delivery.max_attempts', (int) $d['max_attempts'], 'interop');
        $this->settings->set('interop.delivery.backoff_seconds', array_map('intval', preg_split('/\s*,\s*/', trim($d['backoff']))), 'interop');
        $this->settings->set('interop.credentials.overlap_hours', (int) $d['overlap_hours'], 'interop');
        $this->settings->set('interop.tokens.ttl_days', (int) $d['token_days'], 'interop');
        $this->settings->set('interop.retention.processed_payload_days', (int) $d['processed_days'], 'interop');
        $this->settings->set('interop.retention.dead_payload_days', (int) $d['dead_days'], 'interop');
        $this->settings->set('interop.retention.access_log_days', (int) $d['access_days'], 'interop');
        $this->settings->set('interop.fhir.page_size', (int) $d['page_size'], 'interop');
        $this->settings->set('interop.webhook.tolerance_seconds', (int) $d['tolerance'], 'interop');

        return back()->with('success', __('Saved.'));
    }
}
