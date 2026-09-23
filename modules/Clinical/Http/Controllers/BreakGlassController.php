<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Services\BreakGlassService;
use App\Services\SecurityLogger;
use App\Events\Clinical\BreakGlassAccessGranted;
use Illuminate\Http\Request;

class BreakGlassController extends Controller
{
    public function __construct(
        private BreakGlassService $breakGlassService,
        private SecurityLogger $securityLogger
    ) {}

    public function request(Request $request, Encounter $encounter)
    {
        $this->authorize('clinical.break_glass');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
            'scope' => ['nullable', 'string'],
        ]);

        $access = $this->breakGlassService->requestAccess(
            $encounter,
            $validated['reason'],
            $request->user(),
            $request,
            $validated['scope'] ?? null
        );

        $this->securityLogger->log(
            'BreakGlassAccess',
            [
                'encounter_id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'reason' => $validated['reason'],
                'scope' => $validated['scope'] ?? null,
                'expires_at' => $access->expires_at,
            ],
            \App\Services\SecurityLogger::WARNING,
            $request->user(),
            $request
        );

        event(new BreakGlassAccessGranted($encounter, $access));

        return redirect()->route('admin.encounters.show', $encounter)->with('success', 'Break-glass access granted temporarily. All actions are being audited.');
    }
}
