<?php

namespace App\Services\Quality;

use App\Models\Quality\QaIncident;
use App\Models\Quality\QaInvestigation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Looking into why something happened. The findings name systems and processes, never a person to blame. A second person accepts the investigation of a serious incident;
 * the lead cannot accept their own.
 */
class InvestigationService
{
    public const METHODS = ['five_whys', 'fishbone', 'timeline', 'other'];

    public function __construct(private readonly IncidentService $incidents, private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    public function start(QaIncident $incident, User $lead, string $method, User $by): QaInvestigation
    {
        if ($incident->status === 'closed') {
            throw ValidationException::withMessages(['status' => __('This incident is closed.')]);
        }
        if ($incident->status === 'reported') {
            throw ValidationException::withMessages(['status' => __('Review the incident first.')]);
        }
        if ($incident->investigation) {
            throw ValidationException::withMessages(['status' => __('This incident already has an investigation.')]);
        }
        if (! in_array($method, self::METHODS, true)) {
            throw ValidationException::withMessages(['method' => __('Choose a method.')]);
        }

        return DB::transaction(function () use ($incident, $lead, $method, $by) {
            $inv = QaInvestigation::create(['organization_id' => $incident->organization_id, 'incident_id' => $incident->id, 'lead_id' => $lead->id, 'method' => $method, 'status' => 'open', 'started_at' => now()]);
            $incident->update(['status' => 'investigating']);
            $this->incidents->event($incident, 'investigation', __('Led by :n', ['n' => $lead->name]), $by);
            $this->audit->log('CREATE', QaInvestigation::class, $inv->id, null, ['incident' => $incident->number]);
            $this->alerts->toUsers($incident->organization_id, [$lead->id], 'info', __('You lead the investigation of :n.', ['n' => $incident->number]), QaInvestigation::class, $inv->id, 'qa-inv-'.$inv->id);

            return $inv;
        });
    }

    /** @param list<array<string,mixed>> $causes each with category and text */
    public function save(QaInvestigation $inv, ?string $findings, array $causes): QaInvestigation
    {
        if (! in_array($inv->status, ['open', 'returned'], true)) {
            throw ValidationException::withMessages(['status' => __('This investigation has been handed in.')]);
        }
        $allowed = $this->policy->list('incident.cause_categories');
        $clean = [];
        foreach ($causes as $c) {
            $text = trim(strip_tags((string) ($c['text'] ?? '')));
            if ($text === '') {
                continue;
            }
            if (! in_array($c['category'] ?? '', $allowed, true)) {
                throw ValidationException::withMessages(['causes' => __('Choose a cause category.')]);
            }
            $clean[] = ['category' => $c['category'], 'text' => mb_substr($text, 0, 500)];
        }
        $inv->update(['findings' => $findings === null ? null : mb_substr(strip_tags($findings), 0, 6000), 'root_causes' => $clean]);

        return $inv;
    }

    public function submit(QaInvestigation $inv, User $by): QaInvestigation
    {
        if (! in_array($inv->status, ['open', 'returned'], true)) {
            throw ValidationException::withMessages(['status' => __('This investigation has been handed in.')]);
        }
        if (mb_strlen(trim((string) $inv->findings)) < 20 || empty($inv->root_causes)) {
            throw ValidationException::withMessages(['findings' => __('Write the findings and at least one cause before handing it in.')]);
        }
        $inv->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->incidents->event($inv->incident, 'investigation', __('Handed in for review'), $by);
        $this->alerts->raise($inv->organization_id, 'info', __('An investigation is waiting for review: :n', ['n' => $inv->incident->number]), QaInvestigation::class, $inv->id, 'qa-inv-review-'.$inv->id, 'quality.investigation.review', false);

        return $inv;
    }

    /** A second person accepts it (the incident moves on) or sends it back with a reason. */
    public function review(QaInvestigation $inv, User $by, bool $accept, ?string $note): QaInvestigation
    {
        if ($inv->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => __('Nothing to review.')]);
        }
        if ($this->policy->bool('action.second_person_verifies', true) && $inv->lead_id === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must review the investigation.')]);
        }
        if (! $accept && mb_strlen(trim((string) $note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say what is missing.')]);
        }
        DB::transaction(function () use ($inv, $by, $accept, $note) {
            $inv->update(['status' => $accept ? 'accepted' : 'returned', 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note ? mb_substr($note, 0, 500) : null]);
            $incident = $inv->incident;
            if ($accept && $incident->status === 'investigating') {
                $incident->update(['status' => 'actioned']);
            }
            $this->incidents->event($incident, 'investigation', $accept ? __('Accepted') : __('Sent back: :n', ['n' => $note]), $by);
            $this->audit->log('UPDATE', QaInvestigation::class, $inv->id, null, ['status' => $inv->status]);
        });
        if (! $accept && $inv->lead_id) {
            $this->alerts->toUsers($inv->organization_id, [$inv->lead_id], 'info', __('Your investigation of :n was sent back.', ['n' => $inv->incident->number]), QaInvestigation::class, $inv->id, 'qa-inv-back-'.$inv->id.'-'.now()->timestamp);
        }

        return $inv->refresh();
    }
}
