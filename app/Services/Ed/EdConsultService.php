<?php

namespace App\Services\Ed;

use App\Models\Ed\EdConsult;
use App\Models\Ed\EdVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A request to a specialist, with an answer-by time from the urgency. The overdue alert is raised by EdTimerService. */
class EdConsultService
{
    public function request(EdVisit $visit, User $by, string $specialty, string $urgency, ?string $reason = null): EdConsult
    {
        $sla = config('ed.consult_sla');
        if (! isset($sla[$urgency])) {
            throw ValidationException::withMessages(['urgency' => 'Not a valid choice.']);
        }
        if (! $visit->isActive()) {
            throw ValidationException::withMessages(['visit' => 'This visit is closed.']);
        }

        $now = now();

        return EdConsult::create([
            'organization_id' => $visit->organization_id, 'visit_id' => $visit->id, 'specialty' => $specialty, 'urgency' => $urgency, 'reason' => $reason,
            'status' => EdConsult::REQUESTED, 'requested_at' => $now, 'requested_by' => $by->id, 'due_at' => $now->copy()->addMinutes((int) $sla[$urgency]),
        ]);
    }

    public function respond(EdConsult $consult, User $by, ?string $note = null): EdConsult
    {
        return DB::transaction(function () use ($consult, $by, $note) {
            $locked = EdConsult::query()->lockForUpdate()->findOrFail($consult->id);
            if ($locked->status !== EdConsult::REQUESTED) {
                throw ValidationException::withMessages(['consult' => 'This request is already closed.']);
            }
            $locked->update(['status' => EdConsult::RESPONDED, 'responded_at' => now(), 'responded_by' => $by->id, 'response_note' => $note]);

            return $locked;
        });
    }

    public function cancel(EdConsult $consult): void
    {
        if ($consult->status === EdConsult::REQUESTED) {
            $consult->update(['status' => EdConsult::CANCELLED]);
        }
    }
}
