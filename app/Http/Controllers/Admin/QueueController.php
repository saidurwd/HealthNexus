<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentToken;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Services\Appointments\AppointmentService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(private AppointmentService $appointmentService) {}

    public function index()
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $queue = $this->appointmentService->getTodaysQueue($companyId, $branchId);

        return view('admin.queue.index', compact('queue'));
    }

    public function callNext(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $doctorId = $request->input('doctor_id');

        $token = $this->appointmentService->callNextToken($companyId, $branchId, $doctorId);

        if ($token) {
            return response()->json([
                'success' => true,
                'token' => [
                    'token_number' => $token->token_number,
                    'patient_name' => $token->appointment->patient->full_name,
                    'appointment_time' => $token->appointment->appointment_time->format('H:i'),
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No patients in queue.']);
    }

    public function start(Request $request, $tokenId)
    {
        $token = AppointmentToken::findOrFail($tokenId);
        $this->appointmentService->startToken($token);

        return response()->json(['success' => true]);
    }

    public function complete(Request $request, $tokenId)
    {
        $this->authorize('viewAny', Appointment::class);

        $token = AppointmentToken::findOrFail($tokenId);
        $this->appointmentService->completeToken($token);

        return response()->json(['success' => true]);
    }

    public function searchSlots(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $date = $request->input('date', today()->toDateString());
        $doctorId = $request->input('doctor_id');

        $query = AppointmentSlot::where('company_id', $companyId)
            ->whereDate('slot_datetime', $date)
            ->where('status', 'available')
            ->with('doctor');

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $slots = $query->orderBy('slot_datetime')->get();

        return response()->json([
            'slots' => $slots->map(fn ($slot) => [
                'id' => $slot->id,
                'time' => $slot->slot_datetime->format('H:i'),
                'doctor' => $slot->doctor->name ?? '-',
                'available' => $slot->isAvailable(),
            ]),
        ]);
    }
}
