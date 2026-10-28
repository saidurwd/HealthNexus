<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Models\User;
use App\Services\Portal\MessagingService;
use App\Services\Portal\ServiceRequestService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The messages and requests patients send (S09). Urgent ones first; nothing here creates an order. */
class PortalInboxController extends PortalStaffController
{
    public function __construct(TenantContextResolver $tenant, private readonly MessagingService $messages, private readonly ServiceRequestService $requests)
    {
        parent::__construct($tenant);
    }

    public function threads(Request $request)
    {
        $this->allow($request, 'portal.message.view');
        $status = $request->query('status', 'open');
        $rows = PortalThread::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))->orderByDesc('urgent')->orderBy('reply_due_at')->orderByDesc('last_message_at')->paginate(25)->withQueryString();

        return view('admin.portal.threads', ['rows' => $rows, 'status' => $status]);
    }

    public function thread(Request $request, string $thread)
    {
        $this->allow($request, 'portal.message.view');
        $row = $this->find(PortalThread::class, $thread)->load('patient');
        $this->messages->markRead($row, 'staff');

        return view('admin.portal.thread', ['t' => $row, 'messages' => $row->messages()->with('user:id,name')->orderBy('id')->get(), 'staff' => User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function reply(Request $request, string $thread)
    {
        $this->allow($request, 'portal.message.reply');
        $row = $this->find(PortalThread::class, $thread);
        $d = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $this->messages->staffReply($row, $request->user(), $d['body']);

        return back()->with('success', __('Reply sent.'));
    }

    public function assign(Request $request, string $thread)
    {
        $this->allow($request, 'portal.message.reply');
        $row = $this->find(PortalThread::class, $thread);
        $d = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $row->update(['assigned_to' => $d['assigned_to'] ?? null]);

        return back()->with('success', __('Saved.'));
    }

    public function close(Request $request, string $thread)
    {
        $this->allow($request, 'portal.message.reply');
        $this->messages->close($this->find(PortalThread::class, $thread));

        return back()->with('success', __('Closed.'));
    }

    public function requests(Request $request)
    {
        $this->allow($request, 'portal.request.manage');
        $status = $request->query('status', 'open');
        $rows = PortalServiceRequest::query()->with('patient:id,public_id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['requested', 'in_progress']))->when($status !== 'open' && $status !== 'all', fn ($q) => $q->where('status', $status))->orderBy('due_on')->paginate(25)->withQueryString();

        return view('admin.portal.requests', ['rows' => $rows, 'status' => $status]);
    }

    public function updateRequest(Request $request, string $serviceRequest)
    {
        $this->allow($request, 'portal.request.manage');
        $row = $this->find(PortalServiceRequest::class, $serviceRequest);
        $d = $request->validate(['status' => ['required', 'in:in_progress,done,rejected'], 'resolution' => ['nullable', 'string', 'max:300']]);
        $this->requests->update($row, $request->user(), $d['status'], $d['resolution'] ?? null);

        return back()->with('success', __('Saved.'));
    }
}
