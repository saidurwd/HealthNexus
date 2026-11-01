<?php

namespace App\Services\Quality;

use App\Models\Department;
use App\Models\Patient;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIncidentAmendment;
use App\Models\Quality\QaIncidentEvent;
use App\Models\Quality\QaIncidentReporter;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Incidents and near misses. What was reported is frozen; the workflow (triage, owner, investigation, closure) moves separate columns; a correction is an amendment.
 * The reporter is not on the incident: a named reporter has a row in qa_incident_reporters that only `quality.incident.reveal` may open (and every opening is recorded),
 * an anonymous report has no row, no audit entry naming a person and no user on its first event.
 */
class IncidentService
{
    /** Fields a correction may touch. */
    public const AMENDABLE = ['category', 'harm', 'occurred_at', 'location', 'department_id', 'patient_id', 'description', 'immediate_action'];

    public function __construct(private readonly NumberSeriesService $numbers, private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d kind, category, harm, occurred_at, description, immediate_action, location, department_id, branch_id, patient_id, anonymous */
    public function report(int $organizationId, array $d, ?User $by = null): QaIncident
    {
        $category = (string) ($d['category'] ?? '');
        $kind = (string) ($d['kind'] ?? 'incident');
        $harm = (string) ($d['harm'] ?? 'none');
        if (! in_array($category, $this->policy->list('incident.categories'), true)) {
            throw ValidationException::withMessages(['category' => __('Choose what kind of event this was.')]);
        }
        if (! in_array($kind, $this->policy->list('incident.kinds'), true)) {
            throw ValidationException::withMessages(['kind' => __('Choose what you are reporting.')]);
        }
        if (! in_array($harm, $this->policy->list('incident.harm'), true)) {
            throw ValidationException::withMessages(['harm' => __('Choose how much harm it caused.')]);
        }
        $description = trim(strip_tags((string) ($d['description'] ?? '')));
        if (mb_strlen($description) < 10) {
            throw ValidationException::withMessages(['description' => __('Describe what happened in a few words.')]);
        }
        $tz = (string) config('quality.timezone', 'Asia/Dhaka');
        $occurred = CarbonImmutable::parse((string) ($d['occurred_at'] ?? now()->toDateTimeString()), $tz)->utc();
        if ($occurred->isAfter(now()->addMinutes(5))) {
            throw ValidationException::withMessages(['occurred_at' => __('The time cannot be in the future.')]);
        }
        if ($kind === 'near_miss') {
            $harm = 'none';
        }
        if ($harm === 'catastrophic') {
            $kind = 'sentinel';
        }
        $patientId = ! empty($d['patient_id']) ? Patient::query()->where('organization_id', $organizationId)->whereKey($d['patient_id'])->value('id') : null;
        $departmentId = ! empty($d['department_id']) ? Department::query()->where('organization_id', $organizationId)->whereKey($d['department_id'])->value('id') : null;
        $wantsAnonymous = (bool) ($d['anonymous'] ?? false);
        $anonymous = $by === null || $wantsAnonymous;
        if ($wantsAnonymous && ! $this->policy->bool('incident.allow_anonymous', true)) {
            throw ValidationException::withMessages(['anonymous' => __('Reports here must carry your name.')]);
        }
        $dueDays = (int) (((array) $this->policy->get('incident.due_days', []))[$harm] ?? 30);

        $incident = DB::transaction(function () use ($organizationId, $d, $by, $kind, $category, $harm, $description, $occurred, $patientId, $departmentId, $anonymous, $dueDays) {
            $incident = QaIncident::create([
                'organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null,
                'number' => $this->numbers->next('qa.incident', $organizationId, null, prefix: 'INC', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
                'kind' => $kind, 'category' => $category, 'harm' => $harm, 'occurred_at' => $occurred, 'department_id' => $departmentId, 'location' => $this->clip($d['location'] ?? null, 120),
                'patient_id' => $patientId, 'description' => mb_substr($description, 0, 4000), 'immediate_action' => $this->clip($d['immediate_action'] ?? null, 1000), 'is_anonymous' => $anonymous,
                'source_type' => $d['source_type'] ?? null, 'source_id' => $d['source_id'] ?? null, 'status' => 'reported', 'due_on' => now()->addDays($dueDays)->toDateString(),
            ]);
            if (! $anonymous) {
                QaIncidentReporter::create(['organization_id' => $organizationId, 'incident_id' => $incident->id, 'user_id' => $by->id]);
            }
            $this->event($incident, 'reported', null, null);
            if (! $anonymous) {
                $this->audit->log('CREATE', QaIncident::class, $incident->id, null, ['number' => $incident->number, 'category' => $category, 'harm' => $harm]);
            }

            return $incident;
        });

        if ($kind === 'sentinel' || $this->policy->harmRank($harm) >= $this->policy->harmRank('major')) {
            $this->alerts->raise($organizationId, $kind === 'sentinel' ? 'critical' : 'warning', ($kind === 'sentinel' ? __('Sentinel event') : __('Serious incident')).': '.$incident->number.' ('.$category.')',
                QaIncident::class, $incident->id, 'qa-incident-'.$incident->id, 'quality.incident.triage', true, $incident->branch_id);
        }

        return $incident;
    }

    /** Set the review: the harm level after review, an owner and a due date. */
    public function triage(QaIncident $incident, User $by, ?string $reviewHarm, ?User $owner, ?string $dueOn = null): QaIncident
    {
        $this->open($incident);
        if ($reviewHarm !== null && $reviewHarm !== '' && ! in_array($reviewHarm, $this->policy->list('incident.harm'), true)) {
            throw ValidationException::withMessages(['review_harm' => __('Choose a harm level.')]);
        }
        DB::transaction(function () use ($incident, $by, $reviewHarm, $owner, $dueOn) {
            $changes = ['review_harm' => $reviewHarm ?: $incident->review_harm, 'owner_id' => $owner !== null ? $owner->id : $incident->owner_id, 'due_on' => $dueOn ?: $incident->due_on];
            if ($incident->status === 'reported') {
                $changes += ['status' => 'triaged', 'triaged_at' => now()];
            }
            $incident->update($changes);
            $this->event($incident, 'triaged', $reviewHarm ? __('Harm after review: :h', ['h' => $reviewHarm]) : null, $by);
            if ($owner) {
                $this->event($incident, 'assigned', $owner->name, $by);
            }
            $this->audit->log('UPDATE', QaIncident::class, $incident->id, null, ['status' => $incident->status, 'review_harm' => $incident->review_harm]);
        });

        return $incident->refresh();
    }

    /** A correction: the reported text stays; the new value is added with its reason. */
    public function amend(QaIncident $incident, User $by, string $field, ?string $value, string $reason): QaIncidentAmendment
    {
        if (! in_array($field, self::AMENDABLE, true)) {
            throw ValidationException::withMessages(['field' => __('That detail cannot be amended.')]);
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is being changed.')]);
        }
        $value = $value === null ? null : trim($value);
        if ($field === 'harm' && ! in_array($value, $this->policy->list('incident.harm'), true)) {
            throw ValidationException::withMessages(['value' => __('Choose a harm level.')]);
        }
        if ($field === 'category' && ! in_array($value, $this->policy->list('incident.categories'), true)) {
            throw ValidationException::withMessages(['value' => __('Choose what kind of event this was.')]);
        }
        if (in_array($field, ['description'], true) && mb_strlen((string) $value) < 10) {
            throw ValidationException::withMessages(['value' => __('Describe what happened in a few words.')]);
        }
        $incident->loadMissing('amendments');
        $old = $incident->effective($field);

        return DB::transaction(function () use ($incident, $by, $field, $value, $reason, $old) {
            $a = QaIncidentAmendment::create(['organization_id' => $incident->organization_id, 'incident_id' => $incident->id, 'field' => $field, 'old_value' => $old === null ? null : (string) $old,
                'new_value' => $value === null ? null : mb_substr(strip_tags($value), 0, 4000), 'reason' => mb_substr($reason, 0, 500), 'amended_by' => $by->id]);
            $this->event($incident, 'amended', $field, $by);
            $this->audit->log('UPDATE', QaIncident::class, $incident->id, [$field => $old], [$field => $value]);

            return $a;
        });
    }

    public function close(QaIncident $incident, User $by, string $note): QaIncident
    {
        $this->open($incident);
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Write what was found and what was done.')]);
        }
        if (in_array($incident->status, ['reported', 'investigating'], true)) {
            throw ValidationException::withMessages(['note' => $incident->status === 'reported' ? __('Review the incident first.') : __('Finish the investigation first.')]);
        }
        if ($this->needsInvestigation($incident) && $incident->investigation?->status !== 'accepted') {
            throw ValidationException::withMessages(['note' => __('This incident needs an investigation that a second person has accepted.')]);
        }
        $open = QaAction::query()->where(['source_type' => 'incident', 'source_id' => $incident->id])->whereIn('status', ['open', 'in_progress'])->count();
        if ($open > 0) {
            throw ValidationException::withMessages(['note' => __(':n action(s) are still open.', ['n' => $open])]);
        }
        DB::transaction(function () use ($incident, $by, $note) {
            $incident->update(['status' => 'closed', 'closure_note' => mb_substr($note, 0, 1000), 'closed_at' => now(), 'closed_by' => $by->id]);
            $this->event($incident, 'closed', null, $by);
            $this->audit->log('UPDATE', QaIncident::class, $incident->id, null, ['status' => 'closed']);
        });

        return $incident->refresh();
    }

    public function reopen(QaIncident $incident, User $by, string $reason): QaIncident
    {
        if ($incident->status !== 'closed') {
            throw ValidationException::withMessages(['reason' => __('Only a closed incident can be reopened.')]);
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is being reopened.')]);
        }
        $incident->update(['status' => $incident->investigation ? 'investigating' : 'triaged', 'closed_at' => null, 'closed_by' => null]);
        $this->event($incident, 'reopened', $reason, $by);
        $this->audit->log('UPDATE', QaIncident::class, $incident->id, ['status' => 'closed'], ['status' => $incident->status]);

        return $incident->refresh();
    }

    /** Open the reporter's identity. Needs a reason; the opening is recorded on the incident and in the audit log. An anonymous report has no one to reveal. */
    public function reveal(QaIncident $incident, User $by, string $reason): ?User
    {
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => __('Say why you need to know who reported this.')]);
        }
        $row = QaIncidentReporter::query()->where('incident_id', $incident->id)->first();
        $this->event($incident, 'revealed', mb_substr($reason, 0, 500), $by);
        $this->audit->log('REVEAL', QaIncident::class, $incident->id, null, ['reason' => mb_substr($reason, 0, 200)]);

        return $row ? User::query()->find($row->user_id) : null;
    }

    public function isReporter(QaIncident $incident, User $user): bool
    {
        return QaIncidentReporter::query()->where(['incident_id' => $incident->id, 'user_id' => $user->id])->exists();
    }

    /** A serious incident, or one the reviewer rated serious, needs an investigation that a second person accepts. */
    public function needsInvestigation(QaIncident $incident): bool
    {
        return $incident->kind === 'sentinel' || $this->policy->harmRank($incident->harmNow()) >= $this->policy->harmRank((string) $this->policy->get('incident.investigate_from', 'major'));
    }

    public function overdue(QaIncident $incident): bool
    {
        return $incident->status !== 'closed' && $incident->due_on !== null && $incident->due_on->lt(today());
    }

    private function open(QaIncident $incident): void
    {
        if ($incident->status === 'closed') {
            throw ValidationException::withMessages(['status' => __('This incident is closed.')]);
        }
    }

    public function event(QaIncident $incident, string $event, ?string $note, ?User $by): QaIncidentEvent
    {
        return QaIncidentEvent::create(['organization_id' => $incident->organization_id, 'incident_id' => $incident->id, 'event' => $event, 'note' => $note === null ? null : mb_substr($note, 0, 1000), 'user_id' => $by?->id]);
    }

    private function clip(mixed $v, int $n): ?string
    {
        $v = trim(strip_tags((string) $v));

        return $v === '' ? null : mb_substr($v, 0, $n);
    }
}
