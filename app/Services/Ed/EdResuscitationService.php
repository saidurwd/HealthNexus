<?php

namespace App\Services\Ed;

use App\Models\Ed\EdResusEvent;
use App\Models\Ed\EdVisit;
use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The resuscitation timeline (append-only) and the Code Blue call. */
class EdResuscitationService
{
    public function __construct(private readonly AlertService $alerts, private readonly EdTriageService $triage) {}

    public function record(EdVisit $visit, User $by, string $type, ?string $detail = null, ?\DateTimeInterface $at = null): EdResusEvent
    {
        if (! in_array($type, config('ed.resus_events'), true)) {
            throw ValidationException::withMessages(['event_type' => 'Unknown resuscitation event.']);
        }

        return DB::transaction(function () use ($visit, $by, $type, $detail, $at) {
            $locked = EdVisit::query()->lockForUpdate()->findOrFail($visit->id);
            if (! $locked->isActive()) {
                throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
            }

            return EdResusEvent::create([
                'organization_id' => $locked->organization_id, 'visit_id' => $locked->id, 'event_type' => $type,
                'detail' => $detail, 'occurred_at' => $at ?? now(), 'recorded_by' => $by->id,
            ]);
        });
    }

    /** Calls the team: one timeline event plus a critical alert, once per minute at most (a double tap does not page twice). */
    public function callCodeBlue(EdVisit $visit, User $by, ?string $location = null): EdResusEvent
    {
        $event = $this->record($visit, $by, 'code_blue_called', $location);

        $this->alerts->raise(new AlertRequest(
            organizationId: $visit->organization_id, category: 'code_blue', severity: 'critical',
            message: "Code Blue — {$visit->visit_number}".($location ? " ({$location})" : ''),
            sourceModule: 'ed', patientId: $visit->patient_id, branchId: $visit->branch_id, sourceType: EdVisit::class, sourceId: $visit->id,
            recipientUserIds: $this->triage->roleUsers($visit->organization_id), dedupKey: "ed-codeblue:{$visit->id}:".now()->format('YmdHi'), raisedBy: $by->id,
        ));

        return $event;
    }

    /** Minutes from the first event, for the running clock. */
    public function elapsedMinutes(EdVisit $visit): ?int
    {
        $first = EdResusEvent::query()->where('visit_id', $visit->id)->min('occurred_at');

        return $first ? (int) abs(now()->diffInMinutes($first)) : null;
    }
}
