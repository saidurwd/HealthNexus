<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Models\Appointment;
use App\Models\Billing\BillingInvoice;
use App\Models\Billing\BillingPayment;
use App\Models\Billing\BillingReceipt;
use App\Models\Crm\CrmComplaint;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmForm;
use App\Models\Patient;
use App\Models\Portal\PortalPayment;
use App\Models\Portal\PortalServiceRequest;
use App\Models\Portal\PortalThread;
use App\Models\Provider;
use App\Services\Crm\ComplaintService;
use App\Services\Crm\FeedbackService;
use App\Services\Crm\FormService;
use App\Services\Portal\MessagingService;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalPresenter;
use App\Services\Portal\ServiceRequestService;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Appointments, bills, messages, requests, ratings, complaints and forms (S03, S06, S09, S11). */
class PortalWebCareController extends PortalWebController
{
    public function __construct(PortalAccess $access, private readonly PortalAppointmentService $appointments, private readonly PortalPaymentService $payments, private readonly MessagingService $messages,
        private readonly ServiceRequestService $requests, private readonly FeedbackService $feedback, private readonly ComplaintService $complaints, private readonly FormService $forms)
    {
        parent::__construct($access);
    }

    // ---- appointments -----------------------------------------------------------------------------------------------------------------------------

    public function appointments(Request $request)
    {
        $ids = $this->ids($request, 'appointments');

        return view('portal.appointments', [
            'upcoming' => $this->appointments->upcoming($ids)->map(fn ($a) => ['a' => $a, 'queue' => $this->appointments->queue($a)]), 'past' => $this->appointments->past($ids, 10),
            'rated' => CrmFeedback::query()->whereIn('patient_id', $ids)->pluck('appointment_id')->all(),
        ]);
    }

    public function book(Request $request)
    {
        $this->subject($request, 'appointments');
        $organizationId = $this->account($request)->organization_id;
        $provider = $request->query('doctor') ? $this->provider($request, (string) $request->query('doctor')) : null;
        $date = $request->query('date') ? Carbon::parse((string) $request->query('date')) : now()->addDay();

        return view('portal.book', ['doctors' => $this->appointments->providers($organizationId)->map(fn ($p) => PortalPresenter::provider($p)), 'doctor' => $provider ? PortalPresenter::provider($provider) : null,
            'date' => $date, 'slots' => $provider ? $this->appointments->slots($provider, $date) : collect(), 'reschedule' => $request->query('reschedule')]);
    }

    public function confirmBooking(Request $request)
    {
        $d = $request->validate(['slot' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:500'], 'reschedule' => ['nullable', 'string', 'max:26']]);
        $account = $this->account($request);
        if (! empty($d['reschedule'])) {
            $old = Appointment::query()->whereIn('patient_id', $this->ids($request, 'appointments'))->where('public_id', $d['reschedule'])->firstOrFail();
            $this->appointments->reschedule($account, $old, $d['slot']);

            return redirect()->route('portal.appointments')->with('success', __('Your appointment was moved.'));
        }
        $this->appointments->book($account, $this->subject($request, 'appointments'), $d['slot'], $d['reason'] ?? null);

        return redirect()->route('portal.appointments')->with('success', __('Your appointment is booked.'));
    }

    public function cancelAppointment(Request $request, string $appointment)
    {
        $row = Appointment::query()->whereIn('patient_id', $this->ids($request, 'appointments'))->where('public_id', $appointment)->firstOrFail();
        $this->appointments->cancel($this->account($request), $row, $request->input('reason'));

        return redirect()->route('portal.appointments')->with('success', __('Your appointment was cancelled.'));
    }

    public function rate(Request $request, string $appointment)
    {
        $d = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'nps' => ['nullable', 'integer', 'between:0,10'], 'comment' => ['nullable', 'string', 'max:1000']]);
        $patient = $this->subject($request, 'feedback');
        $row = Appointment::query()->where('patient_id', $patient->id)->where('public_id', $appointment)->firstOrFail();
        $this->feedback->submit($patient, $row, (int) $d['rating'], isset($d['nps']) ? (int) $d['nps'] : null, $d['comment'] ?? null);

        return back()->with('success', __('Thank you for your feedback.'));
    }

    // ---- bills ------------------------------------------------------------------------------------------------------------------------------------

    public function billing(Request $request)
    {
        $ids = $this->ids($request, 'billing');
        $receipts = BillingReceipt::query()->with('payment:id,amount,payment_date')->whereIn('payment_id', BillingPayment::query()->whereIn('patient_id', $ids)->select('id'))->where('status', '!=', 'voided')->latest('id')->limit(15)->get();

        return view('portal.billing', ['rows' => $this->payments->invoices($ids), 'receipts' => $receipts, 'enabled' => $this->payments->available(), 'methods' => config('portal.payments.methods'), 'key' => PortalPaymentService::newKey()]);
    }

    public function pay(Request $request, string $invoice)
    {
        $d = $request->validate(['method' => ['required', 'string', 'max:12'], 'amount' => ['nullable', 'numeric', 'min:0.01'], 'key' => ['required', 'string', 'max:64']]);
        $row = BillingInvoice::query()->whereIn('patient_id', $this->ids($request, 'billing'))->where('public_id', $invoice)->firstOrFail();
        $payment = $this->payments->initiate($this->account($request), Patient::query()->findOrFail($row->patient_id), $row, $d['method'], $d['key'], isset($d['amount']) ? (string) $d['amount'] : null);

        return view('portal.pay', ['p' => $payment, 'invoice' => $row]);
    }

    /** The patient comes back from the payment provider. Their word is not taken: the provider is asked. */
    public function payReturn(Request $request, string $payment)
    {
        $row = PortalPayment::query()->whereIn('patient_id', $this->ids($request, 'billing'))->where('public_id', $payment)->firstOrFail();
        $row = $this->payments->confirm($row);

        return redirect()->route('portal.billing')->with($row->status === 'succeeded' ? 'success' : 'error', match ($row->status) {
            'succeeded' => __('Payment received. Thank you.'), 'failed' => __('The payment did not go through. Nothing was charged.'), default => __('We are still waiting for the payment provider. Please check again in a minute.'),
        });
    }

    // ---- messages and requests ---------------------------------------------------------------------------------------------------------------------

    public function threads(Request $request)
    {
        return view('portal.messages', ['rows' => PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->orderByDesc('last_message_at')->paginate(15), 'categories' => config('portal.messaging.categories'), 'n' => config('portal.messaging.emergency_number')]);
    }

    public function startThread(Request $request)
    {
        $d = $request->validate(['subject' => ['required', 'string', 'max:160'], 'category' => ['nullable', 'string', 'max:14'], 'body' => ['required', 'string', 'max:4000']]);
        $thread = $this->messages->start($this->account($request), $this->subject($request, 'messages'), $d['subject'], $d['category'] ?? 'general', $d['body']);

        return redirect()->route('portal.threads.show', $thread->public_id)->with($thread->urgent ? 'error' : 'success', $thread->urgent ? __('This looks urgent. Please call :n or go to the nearest emergency department now.', ['n' => config('portal.messaging.emergency_number')]) : __('Your message was sent.'));
    }

    public function thread(Request $request, string $thread)
    {
        $row = PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->where('public_id', $thread)->firstOrFail();
        $this->messages->markRead($row, 'patient');

        return view('portal.thread', ['t' => $row, 'messages' => $row->messages()->orderBy('id')->get(), 'n' => config('portal.messaging.emergency_number')]);
    }

    public function replyThread(Request $request, string $thread)
    {
        $d = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $row = PortalThread::query()->whereIn('patient_id', $this->ids($request, 'messages'))->where('public_id', $thread)->firstOrFail();
        $this->messages->patientReply($this->account($request), $row, $d['body']);

        return back()->with('success', __('Your message was sent.'));
    }

    public function serviceRequests(Request $request)
    {
        return view('portal.requests', ['rows' => PortalServiceRequest::query()->whereIn('patient_id', $this->ids($request, 'messages'))->latest('id')->limit(30)->get(), 'kinds' => config('portal.service_requests.kinds')]);
    }

    public function storeServiceRequest(Request $request)
    {
        $d = $request->validate(['kind' => ['required', 'string', 'max:20'], 'details' => ['nullable', 'string', 'max:2000']]);
        $this->requests->create($this->account($request), $this->subject($request, 'messages'), $d['kind'], $d['details'] ?? null);

        return back()->with('success', __('Your request was received.'));
    }

    // ---- complaints and forms -----------------------------------------------------------------------------------------------------------------------

    public function complaints(Request $request)
    {
        $patient = $this->subject($request, 'feedback');

        return view('portal.complaints', ['rows' => CrmComplaint::query()->where('patient_id', $patient->id)->latest('id')->limit(20)->get(), 'categories' => config('portal.crm.complaint_categories')]);
    }

    public function storeComplaint(Request $request)
    {
        $d = $request->validate(['category' => ['required', 'string', 'max:20'], 'description' => ['required', 'string', 'max:4000'], 'severity' => ['nullable', 'in:low,medium,high']]);
        $patient = $this->subject($request, 'feedback');
        $c = $this->complaints->lodge($patient->organization_id, $d, null, $patient);

        return back()->with('success', __('We are sorry. Your complaint was received and a person will answer by :d.', ['d' => $c->due_at->format('d M')]));
    }

    public function rateComplaint(Request $request, string $number)
    {
        $d = $request->validate(['score' => ['required', 'integer', 'between:1,5']]);
        $c = CrmComplaint::query()->where('patient_id', $this->subject($request, 'feedback')->id)->where('number', $number)->firstOrFail();
        $this->complaints->satisfaction($c, (int) $d['score']);

        return back()->with('success', __('Thank you.'));
    }

    public function forms(Request $request)
    {
        return view('portal.forms', ['rows' => CrmForm::query()->where('organization_id', $this->account($request)->organization_id)->where('status', 'active')->orderBy('title')->get()]);
    }

    public function form(Request $request, string $code)
    {
        return view('portal.form', ['f' => CrmForm::query()->where(['organization_id' => $this->account($request)->organization_id, 'code' => $code, 'status' => 'active'])->firstOrFail()]);
    }

    public function submitForm(Request $request, string $code)
    {
        $form = CrmForm::query()->where(['organization_id' => $this->account($request)->organization_id, 'code' => $code])->firstOrFail();
        $this->forms->submit($form, $this->subject($request, 'feedback'), $this->account($request), (array) $request->input('answers', []));

        return redirect()->route('portal.forms')->with('success', __('Thank you. Your answers were sent.'));
    }

    private function provider(Request $request, string $token): Provider
    {
        try {
            return Provider::query()->where('organization_id', $this->account($request)->organization_id)->where('status', 'active')->findOrFail((int) Crypt::decryptString($token));
        } catch (DecryptException) {
            abort(404);
        }
    }
}
