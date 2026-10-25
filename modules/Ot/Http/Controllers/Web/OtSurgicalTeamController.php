<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgicalTeam;
use App\Models\Provider;
use App\Services\AuditLogger;
use App\Services\Ot\OtSurgicalTeamService;
use Illuminate\Http\Request;

class OtSurgicalTeamController extends Controller
{
    public function __construct(
        private readonly OtSurgicalTeamService $team,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, OtSurgery $surgery)
    {
        $this->authorize('manageTeam', $surgery);

        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'role' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        $provider = Provider::findOrFail($validated['provider_id']);
        $assignment = $this->team->assign($surgery, $provider, $validated['role'], $request->user(), $validated['reason'] ?? null);

        $this->auditLogger->log('CREATE', OtSurgicalTeam::class, $assignment->id, null, $assignment->toArray(), $request);

        return back()->with('success', 'Team member assigned.');
    }

    public function destroy(Request $request, OtSurgicalTeam $assignment)
    {
        $this->authorize('manageTeam', $assignment->surgery);

        $this->team->remove($assignment, $request->input('reason'));

        $this->auditLogger->log('UPDATE', OtSurgicalTeam::class, $assignment->id, null, ['removed_at' => now()], $request);

        return back()->with('success', 'Team member removed.');
    }
}
