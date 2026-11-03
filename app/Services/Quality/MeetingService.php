<?php

namespace App\Services\Quality;

use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaMeeting;
use App\Models\Quality\QaMeetingItem;
use App\Models\Quality\QaMortalityReview;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Patient-safety committees and the morbidity and mortality meeting: agenda, who came, what was said, what was decided, and the actions that follow, each tracked as an action. */
class MeetingService
{
    public function __construct(private readonly ActionService $actions, private readonly IncidentService $incidents, private readonly AuditLogger $audit) {}

    /** @param list<int> $memberIds */
    public function committee(int $organizationId, string $name, string $kind, ?User $chair, array $memberIds = [], ?string $purpose = null): QaCommittee
    {
        if (mb_strlen(trim($name)) < 3 || ! in_array($kind, (array) config('quality.committee.kinds'), true)) {
            throw ValidationException::withMessages(['name' => __('Give the committee a name and a kind.')]);
        }

        return DB::transaction(function () use ($organizationId, $name, $kind, $chair, $memberIds, $purpose) {
            $c = QaCommittee::create(['organization_id' => $organizationId, 'name' => mb_substr(trim($name), 0, 150), 'kind' => $kind, 'purpose' => $purpose ? mb_substr($purpose, 0, 500) : null, 'chair_id' => $chair?->id, 'active' => true]);
            $ids = collect($memberIds)->when($chair, fn ($c2) => $c2->push($chair->id))->unique();
            foreach ($ids as $id) {
                if (DB::table('user_organizations')->where(['organization_id' => $organizationId, 'user_id' => $id])->exists()) {
                    $c->members()->create(['user_id' => $id, 'role' => $chair && $id === $chair->id ? 'chair' : 'member']);
                }
            }

            return $c;
        });
    }

    public function schedule(QaCommittee $committee, string $title, string $at, ?string $agenda, ?string $location): QaMeeting
    {
        if (mb_strlen(trim($title)) < 3 || strtotime($at) === false) {
            throw ValidationException::withMessages(['title' => __('Give the meeting a title and a time.')]);
        }
        $tz = (string) config('quality.timezone', 'Asia/Dhaka');

        return QaMeeting::create(['organization_id' => $committee->organization_id, 'committee_id' => $committee->id, 'title' => mb_substr(trim($title), 0, 200),
            'scheduled_at' => CarbonImmutable::parse($at, $tz)->utc(), 'agenda' => $agenda ? mb_substr($agenda, 0, 4000) : null, 'location' => $location ? mb_substr($location, 0, 120) : null, 'status' => 'planned']);
    }

    /** Put a case on the agenda: an incident, a death review or a free topic. */
    public function addItem(QaMeeting $m, string $kind, ?int $refId, ?string $title = null): QaMeetingItem
    {
        $this->editable($m);
        $refType = null;
        if ($kind === 'incident') {
            $i = QaIncident::query()->where('organization_id', $m->organization_id)->find($refId) ?? throw ValidationException::withMessages(['ref' => __('Unknown incident.')]);
            $refType = 'incident';
            $title ??= $i->number.' — '.str_replace('_', ' ', $i->category);
        } elseif ($kind === 'mortality') {
            $r = QaMortalityReview::query()->where('organization_id', $m->organization_id)->find($refId) ?? throw ValidationException::withMessages(['ref' => __('Unknown review.')]);
            $refType = 'mortality';
            $title ??= $r->number;
        } elseif ($kind !== 'topic' || mb_strlen(trim((string) $title)) < 3) {
            throw ValidationException::withMessages(['title' => __('Name the topic.')]);
        }
        if ($refType && QaMeetingItem::query()->where(['meeting_id' => $m->id, 'ref_type' => $refType, 'ref_id' => $refId])->exists()) {
            throw ValidationException::withMessages(['ref' => __('Already on the agenda.')]);
        }

        return QaMeetingItem::create(['meeting_id' => $m->id, 'kind' => $kind, 'ref_type' => $refType, 'ref_id' => $refType ? $refId : null, 'title' => mb_substr((string) $title, 0, 250), 'sort' => (int) $m->items()->max('sort') + 1]);
    }

    /** @param list<int> $attendeeIds */
    public function hold(QaMeeting $m, array $attendeeIds, User $by): QaMeeting
    {
        if ($m->status !== 'planned') {
            throw ValidationException::withMessages(['status' => __('This meeting has been held.')]);
        }
        $ids = collect($attendeeIds)->map(fn ($i) => (int) $i)->unique()->filter(fn ($id) => DB::table('user_organizations')->where(['organization_id' => $m->organization_id, 'user_id' => $id])->exists())->values()->all();
        $m->update(['status' => 'held', 'held_at' => now(), 'attendees' => $ids]);
        $this->audit->log('UPDATE', QaMeeting::class, $m->id, null, ['held' => true, 'attendees' => count($ids)]);

        return $m;
    }

    /** What was said about one case, what was decided, and optionally the action it produced. */
    public function recordItem(QaMeeting $m, QaMeetingItem $item, User $by, ?string $discussion, string $outcome, ?array $action = null): QaMeetingItem
    {
        if ($m->status !== 'held' || $item->meeting_id !== $m->id) {
            throw ValidationException::withMessages(['status' => __('Record the discussion after the meeting is held and before the minutes are signed.')]);
        }
        if (mb_strlen(trim($outcome)) < 3) {
            throw ValidationException::withMessages(['outcome' => __('Write the outcome.')]);
        }
        DB::transaction(function () use ($m, $item, $by, $discussion, $outcome, $action) {
            $actionId = $item->action_id;
            if ($action && ! empty($action['description'])) {
                $a = $this->actions->create($m->organization_id, ['source_type' => 'meeting', 'source_id' => $m->id, 'action_type' => $action['action_type'] ?? 'improvement', 'description' => $action['description'], 'owner_id' => $action['owner_id'] ?? null, 'due_on' => $action['due_on'] ?? null], $by);
                $actionId = $a->id;
            }
            $item->update(['discussion' => $discussion ? mb_substr(strip_tags($discussion), 0, 6000) : null, 'outcome' => mb_substr(strip_tags($outcome), 0, 1000), 'action_id' => $actionId]);
            if ($item->ref_type === 'mortality') {
                QaMortalityReview::query()->whereKey($item->ref_id)->where('status', 'reviewed')->update(['status' => 'discussed']);
            }
            if ($item->ref_type === 'incident') {
                $incident = QaIncident::query()->find($item->ref_id);
                $incident && $this->incidents->event($incident, 'meeting', $m->title, $by);
            }
        });

        return $item->refresh();
    }

    public function minute(QaMeeting $m, User $by, string $minutes): QaMeeting
    {
        if ($m->status !== 'held') {
            throw ValidationException::withMessages(['status' => __('Hold the meeting before writing the minutes.')]);
        }
        if (mb_strlen(trim($minutes)) < 20) {
            throw ValidationException::withMessages(['minutes' => __('Write the minutes.')]);
        }
        $open = $m->items()->whereNull('outcome')->count();
        if ($open > 0) {
            throw ValidationException::withMessages(['minutes' => __(':n item(s) have no outcome.', ['n' => $open])]);
        }
        $m->update(['status' => 'minuted', 'minutes' => mb_substr(strip_tags($minutes), 0, 12000), 'minuted_by' => $by->id]);
        $this->audit->log('UPDATE', QaMeeting::class, $m->id, null, ['minuted' => true]);

        return $m;
    }

    private function editable(QaMeeting $m): void
    {
        if (! in_array($m->status, ['planned'], true)) {
            throw ValidationException::withMessages(['status' => __('The agenda is closed.')]);
        }
    }
}
