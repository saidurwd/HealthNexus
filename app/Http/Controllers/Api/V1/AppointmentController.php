<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentLifecycleService;
use App\Services\Appointments\ProviderAvailabilityService;
use App\Services\Appointments\QueueService;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentBookingService $bookingService,
        private AppointmentLifecycleService $lifecycle,
        private QueueService $queueService,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $appointments = Appointment::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->when($request->filled('patient_id'), fn ($q, $id) => $q->where('patient_id', $id))
            ->when($request->filled('provider_id'), fn ($q, $id) => $q->where('provider_id', $id))
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('date'), fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->with(['patient', 'provider', 'doctor', 'token'])
            ->paginate(20);

        return ApiResponse::paginated($appointments);
    }

    public function search(Request $request): JsonResponse
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $search = $request->input('q', '');

        $appointments = Appointment::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->when($search, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('appointment_no', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($q3) => $q3->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            }))
            ->paginate(20);

        return ApiResponse::paginated($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'appointment_date' => ['required_without:slot_id', 'nullable', 'date'],
            'appointment_time' => ['required_without:slot_id', 'nullable', 'date_format:H:i'],
            'type' => ['string'],
            'source' => ['string'],
            'reason' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $validated['branch_id'] = app(TenantContextResolver::class)->getBranchId();
        $validated['created_by'] = $request->user()->id;

        $appointment = $this->bookingService->book($validated, $request->user());

        $this->auditLogger->log('CREATE', Appointment::class, $appointment->id, null, $appointment->toArray(), $request);

        return ApiResponse::success($appointment, 'Appointment created successfully', 201);
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return ApiResponse::success($appointment->load(['patient', 'provider', 'doctor', 'token', 'appointmentType']));
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
        ]);

        $appointment->update($validated);

        return ApiResponse::success($appointment, 'Appointment updated successfully');
    }

    public function confirm(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('confirm', $appointment);

        $this->lifecycle->confirm($appointment, $request->user());

        return ApiResponse::success($appointment->fresh(), 'Appointment confirmed');
    }

    public function checkIn(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('checkIn', $appointment);

        $this->lifecycle->checkIn($appointment, $request->user());

        return ApiResponse::success($appointment->fresh(), 'Patient checked in');
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('cancel', $appointment);

        $validated = $request->validate(['reason' => ['required', 'string']]);

        $this->lifecycle->cancel($appointment, $validated['reason'], $request->user());

        return ApiResponse::success($appointment->fresh(), 'Appointment cancelled');
    }

    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('reschedule', $appointment);

        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'reason' => ['nullable', 'string'],
        ]);

        $this->lifecycle->reschedule($appointment, $validated, $request->user());

        return ApiResponse::success($appointment->fresh(), 'Appointment rescheduled');
    }

    public function noShow(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('markNoShow', $appointment);

        $validated = $request->validate(['reason' => ['nullable', 'string']]);

        $this->lifecycle->markNoShow($appointment, $validated['reason'] ?? null, $request->user());

        return ApiResponse::success($appointment->fresh(), 'Appointment marked as no-show');
    }

    public function queue(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return ApiResponse::success($appointment->token);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'date' => ['required', 'date'],
        ]);

        $provider = \App\Models\Provider::findOrFail($validated['provider_id']);
        $slots = app(ProviderAvailabilityService::class)->availableSlotsOn($provider, Carbon::parse($validated['date']));

        return ApiResponse::success($slots->map(fn (AppointmentSlot $slot) => [
            'id' => $slot->id,
            'time' => $slot->slot_datetime->format('H:i'),
            'remaining_capacity' => $slot->remainingCapacity(),
        ]));
    }

    public function calendar(Request $request): JsonResponse
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $date = $request->input('date', today()->toDateString());

        $appointments = Appointment::query()
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->whereDate('appointment_date', $date)
            ->with(['patient', 'provider', 'token'])
            ->orderBy('appointment_time')
            ->get();

        return ApiResponse::success($appointments);
    }
}
