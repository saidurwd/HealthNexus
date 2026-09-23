<?php

namespace Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentToken;
use App\Models\Provider;
use App\Services\Appointments\QueueService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        private QueueService $queueService,
        private AuditLogger $auditLogger,
    ) {}

    public function index()
    {
        $this->authorize('manageQueue', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $queue = $this->queueService->todaysQueue($companyId, $branchId);

        return view('admin.queue.index', compact('queue'));
    }

    public function callNext(Request $request)
    {
        $this->authorize('manageQueue', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $providerId = $request->input('provider_id');
        $tokenId = $request->input('token_id');

        $token = $this->queueService->callNext($companyId, $branchId, $providerId, $tokenId);

        if ($token) {
            $this->auditLogger->log('CALLED', AppointmentToken::class, $token->id, null, ['token_number' => $token->token_number], $request);

            return response()->json([
                'success' => true,
                'token' => [
                    'id' => $token->id,
                    'token_number' => $token->token_number,
                    'priority' => $token->priority,
                    'patient_name' => $token->appointment->patient->full_name,
                    'appointment_time' => $token->appointment->appointment_time->format('H:i'),
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No patients in queue.']);
    }

    public function recall(Request $request, AppointmentToken $token)
    {
        $this->authorize('manageQueue', Appointment::class);

        $this->queueService->recall($token);

        return response()->json(['success' => true]);
    }

    public function skip(Request $request, AppointmentToken $token)
    {
        $this->authorize('manageQueue', Appointment::class);

        $this->queueService->skip($token);
        $this->auditLogger->log('SKIPPED', AppointmentToken::class, $token->id, null, null, $request);

        return response()->json(['success' => true]);
    }

    public function transfer(Request $request, AppointmentToken $token)
    {
        $this->authorize('manageQueue', Appointment::class);

        $validated = $request->validate(['provider_id' => ['required', 'integer', 'exists:providers,id']]);
        $provider = Provider::findOrFail($validated['provider_id']);

        $this->queueService->transfer($token, $provider);
        $this->auditLogger->log('TRANSFERRED', AppointmentToken::class, $token->id, null, ['provider_id' => $provider->id], $request);

        return response()->json(['success' => true]);
    }

    public function cancel(Request $request, AppointmentToken $token)
    {
        $this->authorize('manageQueue', Appointment::class);

        $this->queueService->cancel($token);

        return response()->json(['success' => true]);
    }

    public function start(Request $request, AppointmentToken $token)
    {
        $this->authorize('checkIn', $token->appointment);

        app(\App\Services\Appointments\AppointmentLifecycleService::class)->startConsultation($token->appointment, $request->user());

        return response()->json(['success' => true]);
    }

    public function complete(Request $request, AppointmentToken $token)
    {
        $this->authorize('update', $token->appointment);

        app(\App\Services\Appointments\AppointmentLifecycleService::class)->complete($token->appointment, $request->user());

        return response()->json(['success' => true]);
    }

    public function searchSlots(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $date = $request->input('date', today()->toDateString());
        $doctorId = $request->input('doctor_id');
        $providerId = $request->input('provider_id');

        $query = AppointmentSlot::where('company_id', $companyId)
            ->whereDate('slot_datetime', $date)
            ->where('status', 'available')
            ->with(['doctor', 'schedule']);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($providerId) {
            $query->whereHas('schedule', fn ($q) => $q->where('provider_id', $providerId));
        }

        $slots = $query->orderBy('slot_datetime')->get();

        return response()->json([
            'slots' => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'time' => $slot->slot_datetime->format('H:i'),
                'doctor' => $slot->doctor->name ?? '-',
                'remaining_capacity' => $slot->remainingCapacity(),
                'available' => $slot->isAvailable(),
            ]),
        ]);
    }
}
