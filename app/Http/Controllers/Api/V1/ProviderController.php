<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Provider;
use App\Services\Appointments\ProviderAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $providers = Provider::query()
            ->when(! $user->hasRole('super_admin'), fn ($q) => $q->whereIn('company_id', $user->companies()->pluck('companies.id')))
            ->where('status', 'active')
            ->with(['department', 'specialty'])
            ->get();

        return ApiResponse::success($providers);
    }

    public function schedule(Provider $provider): JsonResponse
    {
        return ApiResponse::success($provider->schedules()->with(['department', 'room'])->get());
    }

    public function availability(Request $request, Provider $provider): JsonResponse
    {
        $validated = $request->validate(['date' => ['required', 'date']]);

        $slots = app(ProviderAvailabilityService::class)->availableSlotsOn($provider, Carbon::parse($validated['date']));

        return ApiResponse::success($slots->map(fn ($slot) => [
            'id' => $slot->id,
            'time' => $slot->slot_datetime->format('H:i'),
            'remaining_capacity' => $slot->remainingCapacity(),
        ]));
    }
}
