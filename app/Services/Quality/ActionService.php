<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAction;
use App\Models\Quality\QaIncident;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corrective and preventive actions (CAPA) with an owner and a due date. The owner says what was done; a second person checks it worked. Used by incidents, audits, meetings,
 * accreditation gaps, mortality reviews and infection control alike (source_type / source_id).
 */
class ActionService
{
    public const SOURCES = ['incident', 'audit', 'meeting', 'assessment', 'mortality', 'infection', 'outbreak'];

    public function __construct(private readonly NumberSeriesService $numbers, private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d source_type, source_id, action_type, description, owner_id, due_on, branch_id */
    public function create(int $organizationId, array $d, User $by): QaAction
    {
        $type = (string) ($d['action_type'] ?? '');
        if (! in_array($type, ['immediate', 'corrective', 'preventive', 'improvement'], true)) {
            throw ValidationException::withMessages(['action_type' => __('Choose the kind of action.')]);
        }
        if (mb_strlen(trim((string) ($d['description'] ?? ''))) < 5) {
            throw ValidationException::withMessages(['description' => __('Say what is to be done.')]);
        }
        $owner = User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->find($d['owner_id'] ?? 0)
            ?? throw ValidationException::withMessages(['owner_id' => __('Choose who will do it.')]);
        $due = $d['due_on'] ?? now()->addDays($this->policy->int('action.default_days', 30))->toDateString();
        if (strtotime((string) $due) < strtotime(today()->toDateString())) {
            throw ValidationException::withMessages(['due_on' => __('The due date cannot be in the past.')]);
        }
        $source = $d['source_type'] ?? null;
        if ($source !== null && ! in_array($source, self::SOURCES, true)) {
            throw ValidationException::withMessages(['source_type' => __('Unknown source.')]);
        }
        $incident = $source === 'incident' ? QaIncident::query()->where('organization_id', $organizationId)->find($d['source_id'] ?? 0) : null;
        if ($source === 'incident' && ! $incident) {
            throw ValidationException::withMessages(['source_id' => __('Unknown incident.')]);
        }
        if ($incident && $incident->status === 'closed') {
            throw ValidationException::withMessages(['source_id' => __('This incident is closed.')]);
        }

        $action = DB::transaction(function () use ($organizationId, $d, $by, $type, $owner, $due, $source, $incident) {
            $action = QaAction::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? $incident?->branch_id, 'source_type' => $source, 'source_id' => $d['source_id'] ?? null,
                'number' => $this->numbers->next('qa.action', $organizationId, null, prefix: 'CAPA', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
                'action_type' => $type, 'description' => mb_substr(trim(strip_tags((string) $d['description'])), 0, 1000), 'owner_id' => $owner->id, 'due_on' => $due, 'status' => 'open', 'created_by' => $by->id]);
            if ($incident) {
                app(IncidentService::class)->event($incident, 'action', $action->number, $by);
            }
            $this->audit->log('CREATE', QaAction::class, $action->id, null, ['number' => $action->number, 'owner' => $owner->id, 'due_on' => (string) $due]);

            return $action;
        });
        $this->alerts->toUsers($organizationId, [$owner->id], 'info', __('An action is yours: :n, due :d.', ['n' => $action->number, 'd' => (string) $due]), QaAction::class, $action->id, 'qa-action-'.$action->id);

        return $action;
    }

    public function start(QaAction $action, User $by): QaAction
    {
        $this->mustBe($action, ['open'], __('Only an open action can be started.'));
        $action->update(['status' => 'in_progress']);

        return $action;
    }

    /** The owner (or a quality manager) says what was done. */
    public function complete(QaAction $action, User $by, string $note): QaAction
    {
        $this->mustBe($action, ['open', 'in_progress'], __('This action is not open.'));
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say what was done.')]);
        }
        $action->update(['status' => 'done', 'done_at' => now(), 'done_note' => mb_substr(trim(strip_tags($note)), 0, 1000)]);
        $this->audit->log('UPDATE', QaAction::class, $action->id, null, ['status' => 'done']);
        $this->alerts->raise($action->organization_id, 'info', __('An action is waiting to be checked: :n', ['n' => $action->number]), QaAction::class, $action->id, 'qa-action-check-'.$action->id, 'quality.action.verify', false);

        return $action;
    }

    /** A second person checks it. If it did not work the action goes back to its owner with the reason. */
    public function verify(QaAction $action, User $by, bool $effective, ?string $note): QaAction
    {
        $this->mustBe($action, ['done'], __('Only a finished action can be checked.'));
        if ($this->policy->bool('action.second_person_verifies', true) && $action->owner_id === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must check the action.')]);
        }
        if (! $effective && mb_strlen(trim((string) $note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say why it did not work.')]);
        }
        $action->update($effective
            ? ['status' => 'verified', 'verified_by' => $by->id, 'verified_at' => now(), 'effective' => true, 'verify_note' => $note ? mb_substr($note, 0, 500) : null]
            : ['status' => 'open', 'done_at' => null, 'verified_by' => $by->id, 'verified_at' => now(), 'effective' => false, 'verify_note' => mb_substr((string) $note, 0, 500), 'overdue_alerted_at' => null]);
        $this->audit->log('UPDATE', QaAction::class, $action->id, null, ['status' => $action->status, 'effective' => $effective]);
        if (! $effective) {
            $this->alerts->toUsers($action->organization_id, [$action->owner_id], 'warning', __('Action :n needs more work: :r', ['n' => $action->number, 'r' => (string) $note]), QaAction::class, $action->id, 'qa-action-back-'.$action->id.'-'.now()->timestamp);
        }

        return $action->refresh();
    }

    public function cancel(QaAction $action, User $by, string $reason): QaAction
    {
        $this->mustBe($action, ['open', 'in_progress'], __('This action cannot be cancelled.'));
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is cancelled.')]);
        }
        $action->update(['status' => 'cancelled', 'verify_note' => mb_substr($reason, 0, 500)]);
        $this->audit->log('UPDATE', QaAction::class, $action->id, null, ['status' => 'cancelled']);

        return $action;
    }

    /** Tell the owner and the quality managers once about each action past its date. @return int actions newly alerted */
    public function alertOverdue(int $organizationId): int
    {
        $limit = today()->subDays($this->policy->int('action.overdue_alert_after_days', 0))->toDateString();
        $late = QaAction::query()->where('organization_id', $organizationId)->whereIn('status', ['open', 'in_progress'])->where('due_on', '<', $limit)->whereNull('overdue_alerted_at')->get();
        foreach ($late as $a) {
            $this->alerts->toUsers($organizationId, [$a->owner_id], 'warning', __('Action :n is overdue (due :d).', ['n' => $a->number, 'd' => $a->due_on->toDateString()]), QaAction::class, $a->id, 'qa-late-'.$a->id);
            $this->alerts->raise($organizationId, 'warning', __('Action :n is overdue (due :d).', ['n' => $a->number, 'd' => $a->due_on->toDateString()]), QaAction::class, $a->id, 'qa-late-mgr-'.$a->id, 'quality.action.manage', false, $a->branch_id);
            $a->update(['overdue_alerted_at' => now()]);
        }

        return $late->count();
    }

    /** @param list<string> $states */
    private function mustBe(QaAction $action, array $states, string $message): void
    {
        if (! in_array($action->status, $states, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
