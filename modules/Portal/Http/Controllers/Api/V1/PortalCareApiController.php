<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Models\Appointment;
use App\Models\Billing\BillingReceipt;
use App\Models\Patient;
use App\Models\Portal\PortalRefillRequest;
use App\Models\Portal\PortalResultRelease;
use App\Models\Prescription;
use App\Models\Provider;
use App\Services\Portal\DocumentShareService;
use App\Services\Portal\PortalAccess;
use App\Services\Portal\PortalAppointmentService;
use App\Services\Portal\PortalAudit;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPresenter;
use App\Services\Portal\PortalRecordsService;
use App\Services\Portal\RefillService;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Appointments, records, results, prescriptions (S03, S04, S05). */
class PortalCareApiController extends PortalApiController
{
    public function __construct(PortalAccess $access, private readonly PortalAppointmentService $appointments, private readonly PortalRecordsService $records,
        private readonly RefillService $refills, private readonly DocumentShareService $documents, private readonly PortalAudit $audit)
    {
        parent::__construct($access);
    }

    public function providers(Request $request): JsonResponse
    {
        $account = $this->account($request);

        return $this->ok($this->appointments->providers($account->organization_id, $request->integer('specialty_id') ?: null)->map(fn ($p) => PortalPresenter::provider($p))->values()->all());
    }

    public function slots(Request $request, string $provider): JsonResponse
    {
        $request->validate(['date' => ['required', 'date']]);
        $row = $this->provider($request, $provider);

        return $this->ok($this->appointments->slots($row, Carbon::parse($request->input('date')))->map(fn ($s) => ['slot' => $s['token'], 'starts_at' => $s['starts_at']->toIso8601String(), 'minutes' => $s['minutes']])->all());
    }

    public function appointments(Request $request): JsonResponse
    {
        $ids = $this->ids($request, 'appointments');

        return $this->ok([
            'upcoming' => $this->appointments->upcoming($ids)->map(fn ($a) => PortalPresenter::appointment($a) + ['queue' => $this->appointments->queue($a)])->values()->all(),
            'past' => $this->appointments->past($ids)->map(fn ($a) => PortalPresenter::appointment($a))->values()->all(),
        ]);
    }

    public function book(Request $request): JsonResponse
    {
        $d = $request->validate(['slot' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:500']]);
        $patient = $this->subject($request, 'appointments');
        $appointment = $this->appointments->book($this->account($request), $patient, $d['slot'], $d['reason'] ?? null);

        return $this->ok(PortalPresenter::appointment($appointment->load(['provider', 'branch', 'token'])), __('Your appointment is booked.'), [], 201);
    }

    public function cancel(Request $request, string $appointment): JsonResponse
    {
        $row = $this->appointment($request, $appointment);
        $this->appointments->cancel($this->account($request), $row, $request->input('reason'));

        return $this->ok(null, __('Your appointment was cancelled.'));
    }

    public function reschedule(Request $request, string $appointment): JsonResponse
    {
        $d = $request->validate(['slot' => ['required', 'string']]);
        $new = $this->appointments->reschedule($this->account($request), $this->appointment($request, $appointment), $d['slot']);

        return $this->ok(PortalPresenter::appointment($new->load(['provider', 'branch', 'token'])), __('Your appointment was moved.'));
    }

    public function queue(Request $request, string $appointment): JsonResponse
    {
        return $this->ok($this->appointments->queue($this->appointment($request, $appointment)->load('token')));
    }

    public function summary(Request $request): JsonResponse
    {
        $patient = $this->subject($request, 'results');
        $this->audit->access($this->account($request), $patient->id, 'view_record');
        $s = $this->records->summary($patient);

        return $this->ok(['allergies' => $s['allergies'], 'problems' => $s['problems'], 'vitals' => $s['vitals'], 'visits' => $s['visits']->all(), 'stays' => $this->records->stays($patient->id), 'insurance' => $this->records->insurance($patient->id)]);
    }

    public function results(Request $request): JsonResponse
    {
        $ids = $this->ids($request, 'results');

        return $this->ok([
            'released' => $this->records->results($ids)->map(fn ($r) => PortalPresenter::result($r))->values()->all(),
            'not_yet_available' => $this->records->pendingNotice($ids)->map(fn ($r) => ['title' => $r->title, 'reported_at' => $r->reported_at?->toIso8601String()])->values()->all(),
        ]);
    }

    public function result(Request $request, string $result): JsonResponse
    {
        $release = $this->records->findReleased($this->ids($request, 'results'), $result);
        $this->audit->access($this->account($request), $release->patient_id, 'view_result', $release);

        return $this->ok($this->records->view($release));
    }

    public function prescriptions(Request $request): JsonResponse
    {
        $ids = $this->ids($request, 'prescriptions');

        return $this->ok($this->records->prescriptions($ids)->map(fn ($p) => PortalPresenter::prescription($p))->values()->all());
    }

    public function refill(Request $request, string $prescription): JsonResponse
    {
        $d = $request->validate(['note' => ['nullable', 'string', 'max:300']]);
        $key = (string) $request->header('Idempotency-Key', '');
        if ($key === '') {
            throw new PortalException(__('An Idempotency-Key header is needed.'));
        }
        $rx = Prescription::query()->whereIn('patient_id', $this->ids($request, 'prescriptions'))->where('public_id', $prescription)->firstOrFail();
        $row = $this->refills->request($this->account($request), Patient::query()->findOrFail($rx->patient_id), $rx, $d['note'] ?? null, $key);

        return $this->ok(['id' => $row->public_id, 'status' => $row->status], __('Your request was sent to the pharmacy team. A person will review it.'), [], 201);
    }

    public function refillRequests(Request $request): JsonResponse
    {
        return $this->ok(PortalRefillRequest::query()->whereIn('patient_id', $this->ids($request, 'prescriptions'))->latest('id')->limit(30)->get()->map(fn ($r) => ['id' => $r->public_id, 'status' => $r->status, 'note' => $r->decision_note, 'at' => $r->created_at->toIso8601String()])->all());
    }

    public function share(Request $request): JsonResponse
    {
        $d = $request->validate(['type' => ['required', 'in:lab_report,radiology_report,receipt,prescription'], 'id' => ['required', 'string']]);
        $patient = $this->subject($request, 'documents');
        $sourceId = $this->sourceId($d['type'], $d['id'], $patient->id);
        [$doc, $plain] = $this->documents->share($patient->id, $d['type'], $sourceId, $this->account($request));

        return $this->ok(['url' => url('/api/v1/portal/documents/'.$plain), 'expires_at' => $doc->expires_at->toIso8601String(), 'title' => $doc->title], __('This link stops working after :d days.', ['d' => config('portal.documents.link_ttl_days')]), [], 201);
    }

    public function document(Request $request, string $token): JsonResponse
    {
        [$doc, $content] = $this->documents->open($this->account($request), $token, $this->access);
        $data = $content['data'];

        return $this->ok(['title' => $doc->title, 'type' => $content['type'], 'content' => is_array($data) ? $data : $data->toArray()]);
    }

    private function provider(Request $request, string $token): Provider
    {
        try {
            return Provider::query()->where('organization_id', $this->account($request)->organization_id)->where('status', 'active')->findOrFail((int) Crypt::decryptString($token));
        } catch (DecryptException) {
            abort(404);
        }
    }

    private function appointment(Request $request, string $publicId): Appointment
    {
        return Appointment::query()->whereIn('patient_id', $this->ids($request, 'appointments'))->where('public_id', $publicId)->firstOrFail();
    }

    private function sourceId(string $type, string $publicId, int $patientId): int
    {
        return match ($type) {
            'lab_report', 'radiology_report' => (int) PortalResultRelease::query()->where(['patient_id' => $patientId, 'public_id' => $publicId])->firstOrFail()->source_id,
            'receipt' => (int) BillingReceipt::query()->where('public_id', $publicId)->firstOrFail()->id,
            'prescription' => (int) Prescription::query()->where(['patient_id' => $patientId, 'public_id' => $publicId])->firstOrFail()->id,
            default => abort(404),
        };
    }
}
