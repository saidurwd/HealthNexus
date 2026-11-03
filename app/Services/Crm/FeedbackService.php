<?php

namespace App\Services\Crm;

use App\Models\Appointment;
use App\Models\Crm\CrmFeedback;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPolicy;
use Illuminate\Validation\ValidationException;

/** How patients rate a visit (S11). One rating per visit; a low rating opens a follow-up for the patient-relations team. */
class FeedbackService
{
    public function __construct(private readonly InteractionService $interactions, private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    public function submit(Patient $patient, ?Appointment $appointment, int $rating, ?int $nps, ?string $comment, string $channel = 'portal'): CrmFeedback
    {
        if ($rating < 1 || $rating > 5 || ($nps !== null && ($nps < 0 || $nps > 10))) {
            throw ValidationException::withMessages(['rating' => __('Choose a rating from 1 to 5.')]);
        }
        if ($appointment) {
            if ($appointment->patient_id !== $patient->id || $appointment->status !== 'completed') {
                throw new PortalException(__('You can rate a visit after it is finished.'), 409);
            }
            if ($appointment->appointment_date->lt(now()->subDays($this->policy->int('crm.feedback_window_days', 14))->startOfDay())) {
                throw new PortalException(__('This visit is too long ago to rate.'), 409);
            }
            if (CrmFeedback::query()->where(['patient_id' => $patient->id, 'appointment_id' => $appointment->id])->exists()) {
                throw new PortalException(__('You have already rated this visit.'), 409);
            }
        }
        $row = CrmFeedback::create(['organization_id' => $patient->organization_id, 'branch_id' => $appointment?->branch_id, 'patient_id' => $patient->id, 'appointment_id' => $appointment?->id, 'provider_id' => $appointment?->provider_id,
            'channel' => $channel, 'rating' => $rating, 'nps' => $nps, 'comment' => $comment ? mb_substr(strip_tags($comment), 0, 1000) : null]);
        $this->audit->log('CREATE', CrmFeedback::class, $row->id, null, ['rating' => $rating]);
        if ($rating <= 2) { // a person must look at a poor rating
            $this->interactions->log($patient->organization_id, null, ['patient_id' => $patient->id, 'branch_id' => $appointment?->branch_id, 'channel' => 'portal', 'direction' => 'in', 'subject' => 'Low rating after a visit: follow up with the patient',
                'summary' => $comment ? mb_substr(strip_tags($comment), 0, 500) : null, 'follow_up_on' => now()->addDay()->toDateString(), 'status' => 'open']);
        }

        return $row;
    }

    public function review(CrmFeedback $row, User $by): CrmFeedback
    {
        $row->update(['status' => 'reviewed', 'reviewed_by' => $by->id]);

        return $row;
    }

    /** @return array{count:int,average:?float,nps:?int,low:int} */
    public function summary(int $organizationId, ?string $from = null): array
    {
        $q = CrmFeedback::query()->where('organization_id', $organizationId)->when($from, fn ($w) => $w->where('created_at', '>=', $from));
        $count = (clone $q)->count();
        $nps = (clone $q)->whereNotNull('nps');
        $n = (clone $nps)->count();
        $promoters = (clone $nps)->where('nps', '>=', 9)->count();
        $detractors = (clone $nps)->where('nps', '<=', 6)->count();

        return ['count' => $count, 'average' => $count ? round((float) (clone $q)->avg('rating'), 2) : null, 'nps' => $n ? (int) round(100 * ($promoters - $detractors) / $n) : null, 'low' => (clone $q)->where('rating', '<=', 2)->count()];
    }
}
