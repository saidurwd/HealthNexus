<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmForm;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FeedbackService;
use App\Services\Crm\FormService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalPresenter;
use App\Services\Portal\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Messages, requests, feedback, complaints and forms (S09, S11). */
class PortalTalkApiController extends PortalApiController
{
    public function __construct(PortalAccess $access, private readonly MessagingService $messages, private readonly ServiceRequestService $requests,
        private readonly FeedbackService $feedback, private readonly ComplaintService $complaints, private readonly FormService $forms)
    {
        parent::__construct($access);
    }

    public function threads(Request $request): JsonResponse
    {
        $rows = PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->orderByDesc('last_message_at')->paginate($this->page($request));

        return $this->ok($rows->getCollection()->map(fn ($t) => PortalPresenter::thread($t))->values()->all(), 'OK', ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total(),
            'notice' => __('Messages are not for emergencies. In an emergency call :n.', ['n' => config('portal.messaging.emergency_number')])]);
    }

    public function thread(Request $request, string $thread): JsonResponse
    {
        $row = PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->where('public_id', $thread)->firstOrFail();
        $this->messages->markRead($row, 'patient');

        return $this->ok(PortalPresenter::thread($row, true));
    }

    public function start(Request $request): JsonResponse
    {
        $d = $request->validate(['subject' => ['required', 'string', 'max:160'], 'category' => ['nullable', 'string', 'max:14'], 'body' => ['required', 'string', 'max:4000']]);
        $thread = $this->messages->start($this->account($request), $this->subject($request, 'messages'), $d['subject'], $d['category'] ?? 'general', $d['body']);

        return $this->ok(PortalPresenter::thread($thread, true), $thread->urgent ? __('This looks urgent. Please call :n or go to the nearest emergency department now.', ['n' => config('portal.messaging.emergency_number')]) : __('Your message was sent.'), ['urgent' => $thread->urgent], 201);
    }

    public function reply(Request $request, string $thread): JsonResponse
    {
        $d = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $row = PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->where('public_id', $thread)->firstOrFail();
        $this->messages->patientReply($this->account($request), $row, $d['body']);

        return $this->ok(PortalPresenter::thread($row->fresh(), true), __('Your message was sent.'), ['urgent' => $row->fresh()->urgent], 201);
    }

    public function serviceRequests(Request $request): JsonResponse
    {
        return $this->ok(PortalServiceRequest::query()->whereIn('patient_id', $this->ids($request, 'messages'))->latest('id')->limit(30)->get()->map(fn ($r) => ['id' => $r->public_id, 'kind' => $r->kind, 'status' => $r->status, 'answer' => $r->resolution, 'due_on' => $r->due_on?->toDateString()])->all());
    }

    public function storeServiceRequest(Request $request): JsonResponse
    {
        $d = $request->validate(['kind' => ['required', 'string', 'max:20'], 'details' => ['nullable', 'string', 'max:2000']]);
        $row = $this->requests->create($this->account($request), $this->subject($request, 'messages'), $d['kind'], $d['details'] ?? null);

        return $this->ok(['id' => $row->public_id, 'status' => $row->status], __('Your request was received.'), [], 201);
    }

    public function rate(Request $request, string $appointment): JsonResponse
    {
        $d = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'nps' => ['nullable', 'integer', 'between:0,10'], 'comment' => ['nullable', 'string', 'max:1000']]);
        $patient = $this->subject($request, 'feedback');
        $row = Appointment::query()->where('patient_id', $patient->id)->where('public_id', $appointment)->firstOrFail();
        $this->feedback->submit($patient, $row, (int) $d['rating'], isset($d['nps']) ? (int) $d['nps'] : null, $d['comment'] ?? null);

        return $this->ok(null, __('Thank you for your feedback.'), [], 201);
    }

    public function complaint(Request $request): JsonResponse
    {
        $d = $request->validate(['category' => ['required', 'string', 'max:20'], 'description' => ['required', 'string', 'max:4000'], 'severity' => ['nullable', 'in:low,medium,high']]);
        $patient = $this->subject($request, 'feedback');
        $row = $this->complaints->lodge($patient->organization_id, $d, null, $patient);

        return $this->ok(['number' => $row->number, 'status' => $row->status], __('We are sorry. Your complaint was received and a person will answer by :d.', ['d' => $row->due_at->format('d M')]), [], 201);
    }

    public function myComplaints(Request $request): JsonResponse
    {
        return $this->ok(CrmComplaint::query()->where('patient_id', $this->subject($request, 'feedback')->id)->latest('id')->limit(20)->get()->map(fn ($c) => ['number' => $c->number, 'status' => $c->status, 'category' => $c->category, 'answer' => in_array($c->status, ['resolved', 'closed'], true) ? $c->resolution : null, 'rated' => $c->satisfaction !== null])->all());
    }

    public function rateComplaint(Request $request, string $number): JsonResponse
    {
        $d = $request->validate(['score' => ['required', 'integer', 'between:1,5']]);
        $c = CrmComplaint::query()->where('patient_id', $this->subject($request, 'feedback')->id)->where('number', $number)->firstOrFail();
        $this->complaints->satisfaction($c, (int) $d['score']);

        return $this->ok(null, __('Thank you.'));
    }

    public function forms(Request $request): JsonResponse
    {
        return $this->ok(CrmForm::query()->where('organization_id', $this->account($request)->organization_id)->where('status', 'active')->orderBy('title')->get()->map(fn ($f) => ['code' => $f->code, 'title' => $f->title, 'version' => $f->version, 'fields' => $f->fields])->all());
    }

    public function submitForm(Request $request, string $code): JsonResponse
    {
        $form = CrmForm::query()->where(['organization_id' => $this->account($request)->organization_id, 'code' => $code])->firstOrFail();
        $row = $this->forms->submit($form, $this->subject($request, 'feedback'), $this->account($request), (array) $request->input('answers', []));

        return $this->ok(['id' => $row->public_id], __('Thank you. Your answers were sent.'), [], 201);
    }
}
