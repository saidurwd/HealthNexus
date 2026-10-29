<?php

namespace Modules\Interop\Http\Controllers\Web;

use App\Models\Integration\Hl7InboundResult;
use App\Models\Integration\IntegrationAccessLog;
use App\Models\Integration\IntegrationAttempt;
use App\Models\Integration\IntegrationMessage;
use App\Models\Integration\IntegrationPartner;
use App\Services\Interop\InteropException;
use App\Services\Interop\MessageService;
use App\Services\Interop\ReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The monitoring side: what is flowing, what failed, what a person must decide (S02, S08, S11). */
class InteropMessageController extends InteropStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly MessageService $messages, private readonly ReportService $reports)
    {
        parent::__construct($tenant);
    }

    public function dashboard(Request $request)
    {
        $this->allow($request, 'interoperability.dashboard.view');

        return view('admin.interop.dashboard', ['d' => $this->reports->dashboard($this->organizationId())]);
    }

    public function messages(Request $request)
    {
        $this->allow($request, 'interoperability.message.view');
        $org = $this->organizationId();
        $rows = IntegrationMessage::query()->with('partner:id,name,code')->where('organization_id', $org)->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('direction'), fn ($q, $v) => $q->where('direction', $v))
            ->when($request->query('channel'), fn ($q, $v) => $q->where('channel', $v))->when($request->query('partner'), fn ($q, $v) => $q->where('partner_id', IntegrationPartner::query()->where(['organization_id' => $org, 'code' => $v])->value('id')))
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('message_type', 'like', "%$v%")->orWhere('correlation_id', $v)->orWhere('public_id', $v)))->latest('id')->paginate(30)->withQueryString();

        return view('admin.interop.messages', ['rows' => $rows, 'partners' => IntegrationPartner::query()->where('organization_id', $org)->orderBy('code')->get(['code', 'name'])]);
    }

    public function message(Request $request, string $message)
    {
        $this->allow($request, 'interoperability.message.view');
        $m = $this->find(IntegrationMessage::class, $message);
        $canSee = $request->user()->can('interoperability.message.replay');

        return view('admin.interop.message', ['m' => $m, 'attempts' => IntegrationAttempt::query()->where('message_id', $m->id)->orderBy('attempt_no')->get(), 'payload' => $canSee ? mb_substr((string) $m->payload, 0, 6000) : null,
            'response' => $canSee ? mb_substr((string) $m->response, 0, 2000) : null, 'replays' => IntegrationMessage::query()->where('replay_of_id', $m->id)->get(['public_id', 'status', 'created_at']), 'original' => $m->replay_of_id ? IntegrationMessage::query()->find($m->replay_of_id) : null]);
    }

    public function act(Request $request, string $message)
    {
        $this->allow($request, 'interoperability.message.replay');
        $m = $this->find(IntegrationMessage::class, $message);
        $d = $request->validate(['action' => ['required', 'in:replay,dismiss'], 'reason' => ['nullable', 'string', 'max:200']]);
        try {
            if ($d['action'] === 'replay') {
                $copy = $this->messages->replay($m, $request->user());

                return redirect()->route('admin.interop.messages.show', $copy->public_id)->with('success', __('Sent again as a new message.'));
            }
            if (trim((string) ($d['reason'] ?? '')) === '') {
                return back()->withErrors(['reason' => __('Say why it is being set aside.')]);
            }
            $this->messages->dismiss($m, $request->user(), $d['reason']);
        } catch (InteropException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Saved.'));
    }

    public function results(Request $request)
    {
        $this->allow($request, 'interoperability.hl7.review');
        $status = $request->query('status', 'new');
        $rows = Hl7InboundResult::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())->when($status !== 'all', fn ($q) => $q->where('status', $status))->orderByDesc('id')->paginate(40)->withQueryString();

        return view('admin.interop.results', ['rows' => $rows, 'status' => $status]);
    }

    public function review(Request $request, string $result)
    {
        $this->allow($request, 'interoperability.hl7.review');
        $r = $this->find(Hl7InboundResult::class, $result);
        $d = $request->validate(['decision' => ['required', 'in:accepted,rejected'], 'note' => ['nullable', 'string', 'max:300']]);
        if ($r->status !== 'new') {
            return back()->with('error', __('This result was already reviewed.'));
        }
        $r->update(['status' => $d['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'note' => $d['note'] ?? null]);

        return back()->with('success', __('Saved.'));
    }

    public function accessLog(Request $request)
    {
        $this->allow($request, 'interoperability.exchange.log.view');
        $org = $this->organizationId();
        $rows = IntegrationAccessLog::query()->with(['partner:id,name', 'patient:id,first_name,last_name,enterprise_patient_no'])->where('organization_id', $org)->when($request->query('outcome'), fn ($q, $v) => $q->where('outcome', $v))
            ->when($request->query('partner'), fn ($q, $v) => $q->where('partner_id', IntegrationPartner::query()->where(['organization_id' => $org, 'code' => $v])->value('id')))->latest('id')->paginate(40)->withQueryString();

        return view('admin.interop.access-log', ['rows' => $rows, 'partners' => IntegrationPartner::query()->where('organization_id', $org)->orderBy('code')->get(['code', 'name'])]);
    }
}
