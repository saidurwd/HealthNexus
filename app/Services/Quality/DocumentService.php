<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAcknowledgement;
use App\Models\Quality\QaDocument;
use App\Models\Quality\QaDocumentAssignment;
use App\Models\Quality\QaDocumentVersion;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Policies, procedures and protocols under version control. A version is drafted, sent for review, and approved by someone other than its author; an approved version is never edited
 * (a change is a new version, the old one is kept as superseded). Staff are asked to acknowledge the version in force and are asked again when a new one is approved.
 */
class DocumentService
{
    public function __construct(private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d code, title, category, owner_id, department_id, review_months, body */
    public function create(int $organizationId, array $d, User $by): QaDocument
    {
        $code = strtoupper(trim((string) ($d['code'] ?? '')));
        if ($code === '' || ! preg_match('/^[A-Z0-9][A-Z0-9._-]{1,29}$/', $code)) {
            throw ValidationException::withMessages(['code' => __('Give the document a short code (letters, numbers, dash).')]);
        }
        if (QaDocument::query()->where(['organization_id' => $organizationId, 'code' => $code])->exists()) {
            throw ValidationException::withMessages(['code' => __('That code is already used.')]);
        }
        if (! in_array($d['category'] ?? '', $this->policy->list('document.categories'), true)) {
            throw ValidationException::withMessages(['category' => __('Choose the kind of document.')]);
        }
        if (mb_strlen(trim((string) ($d['title'] ?? ''))) < 3 || mb_strlen(trim((string) ($d['body'] ?? ''))) < 10) {
            throw ValidationException::withMessages(['body' => __('Write the title and the text.')]);
        }

        return DB::transaction(function () use ($organizationId, $code, $d, $by) {
            $doc = QaDocument::create(['organization_id' => $organizationId, 'code' => $code, 'title' => mb_substr(trim($d['title']), 0, 200), 'category' => $d['category'], 'owner_id' => $d['owner_id'] ?? $by->id,
                'department_id' => $d['department_id'] ?? null, 'review_months' => (int) ($d['review_months'] ?? $this->policy->int('document.default_review_months', 12)), 'status' => 'active']);
            QaDocumentVersion::create(['organization_id' => $organizationId, 'document_id' => $doc->id, 'version' => 1, 'status' => 'draft', 'body' => $d['body'], 'change_note' => 'First version', 'author_id' => $by->id]);
            $this->audit->log('CREATE', QaDocument::class, $doc->id, null, ['code' => $code]);

            return $doc;
        });
    }

    public function newVersion(QaDocument $doc, string $body, ?string $changeNote, User $by): QaDocumentVersion
    {
        if ($doc->status === 'retired') {
            throw ValidationException::withMessages(['status' => __('This document is retired.')]);
        }
        if ($doc->versions()->whereIn('status', ['draft', 'in_review'])->exists()) {
            throw ValidationException::withMessages(['status' => __('There is already a version being prepared.')]);
        }
        if (mb_strlen(trim($body)) < 10 || mb_strlen(trim((string) $changeNote)) < 3) {
            throw ValidationException::withMessages(['change_note' => __('Write the text and what changed.')]);
        }
        $v = QaDocumentVersion::create(['organization_id' => $doc->organization_id, 'document_id' => $doc->id, 'version' => ((int) $doc->versions()->max('version')) + 1, 'status' => 'draft', 'body' => $body, 'change_note' => mb_substr((string) $changeNote, 0, 500), 'author_id' => $by->id]);
        $this->audit->log('CREATE', QaDocumentVersion::class, $v->id, null, ['document' => $doc->code, 'version' => $v->version]);

        return $v;
    }

    public function edit(QaDocumentVersion $v, string $body, ?string $changeNote): QaDocumentVersion
    {
        if (! in_array($v->status, ['draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => __('Only a draft can be edited: make a new version.')]);
        }
        $v->update(['body' => $body, 'change_note' => $changeNote ? mb_substr($changeNote, 0, 500) : $v->change_note, 'status' => 'draft']);

        return $v;
    }

    public function submit(QaDocumentVersion $v, User $by): QaDocumentVersion
    {
        if ($v->status !== 'draft') {
            throw ValidationException::withMessages(['status' => __('Only a draft can be sent for review.')]);
        }
        $v->update(['status' => 'in_review', 'submitted_at' => now()]);
        $this->alerts->raise($v->organization_id, 'info', __('A document is waiting for approval: :c', ['c' => $v->document->code]), QaDocumentVersion::class, $v->id, 'qa-doc-review-'.$v->id, 'quality.document.approve', false);

        return $v;
    }

    /** Approve (the version takes effect and the previous one is kept as superseded) or send back with a reason. */
    public function decide(QaDocumentVersion $v, User $by, bool $approve, ?string $note, ?string $effectiveOn = null): QaDocumentVersion
    {
        if ($v->status !== 'in_review') {
            throw ValidationException::withMessages(['status' => __('Nothing to decide.')]);
        }
        if ($this->policy->bool('document.second_person_approves', true) && $v->author_id === $by->id) {
            throw ValidationException::withMessages(['status' => __('Someone other than the author must approve.')]);
        }
        if (! $approve && mb_strlen(trim((string) $note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say what must change.')]);
        }

        return DB::transaction(function () use ($v, $by, $approve, $note, $effectiveOn) {
            $doc = QaDocument::query()->lockForUpdate()->findOrFail($v->document_id);
            if (! $approve) {
                $v->update(['status' => 'rejected', 'decision_note' => mb_substr((string) $note, 0, 500), 'approved_by' => $by->id, 'approved_at' => now()]);

                return $v;
            }
            QaDocumentVersion::query()->where('document_id', $doc->id)->where('status', 'approved')->update(['status' => 'superseded']);
            $from = $effectiveOn ?: today()->toDateString();
            $v->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 500) : null, 'effective_on' => $from, 'review_due_on' => date('Y-m-d', strtotime("$from +{$doc->review_months} months"))]);
            $doc->update(['current_version_id' => $v->id]);
            $this->audit->log('UPDATE', QaDocumentVersion::class, $v->id, null, ['approved' => true, 'document' => $doc->code, 'version' => $v->version]);

            return $v;
        });
    }

    public function retire(QaDocument $doc, User $by, string $reason): QaDocument
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is retired.')]);
        }
        $doc->update(['status' => 'retired']);
        $this->audit->log('UPDATE', QaDocument::class, $doc->id, null, ['status' => 'retired', 'reason' => mb_substr($reason, 0, 200)]);

        return $doc;
    }

    public function assign(QaDocument $doc, string $audience, string $value = ''): QaDocumentAssignment
    {
        if (! in_array($audience, ['all', 'role', 'department', 'user'], true)) {
            throw ValidationException::withMessages(['audience' => __('Choose who must read it.')]);
        }
        if ($audience === 'all') {
            $value = '';
        } elseif ($audience === 'role' && ! Role::query()->where('name', $value)->exists()) {
            throw ValidationException::withMessages(['value' => __('Choose a role.')]);
        } elseif ($audience === 'department' && ! DB::table('departments')->where('organization_id', $doc->organization_id)->where('id', $value)->exists()) {
            throw ValidationException::withMessages(['value' => __('Choose a department.')]);
        } elseif ($audience === 'user' && ! DB::table('user_organizations')->where('organization_id', $doc->organization_id)->where('user_id', $value)->exists()) {
            throw ValidationException::withMessages(['value' => __('Choose a person.')]);
        }

        return QaDocumentAssignment::query()->firstOrCreate(['organization_id' => $doc->organization_id, 'document_id' => $doc->id, 'audience' => $audience, 'value' => (string) $value]);
    }

    /** @return Collection<int,int> ids of the staff who must read this document */
    public function audience(QaDocument $doc): Collection
    {
        $users = collect();
        foreach ($doc->assignments as $a) {
            $ids = match ($a->audience) {
                'all' => DB::table('user_organizations')->where('organization_id', $doc->organization_id)->pluck('user_id'),
                'role' => DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', $a->value)->where('model_has_roles.model_type', User::class)
                    ->whereIn('model_has_roles.model_id', DB::table('user_organizations')->where('organization_id', $doc->organization_id)->select('user_id'))->pluck('model_has_roles.model_id'),
                'department' => DB::table('user_departments')->where('department_id', $a->value)->pluck('user_id'),
                default => collect([(int) $a->value]),
            };
            $users = $users->merge($ids);
        }

        return $users->map(fn ($i) => (int) $i)->unique()->values()->filter(fn ($id) => User::query()->where('is_active', true)->whereKey($id)->exists())->values();
    }

    /** Documents in force that this person must still acknowledge. @return Collection<int,QaDocument> */
    public function pendingFor(User $user, int $organizationId): Collection
    {
        return QaDocument::query()->with('current')->where('organization_id', $organizationId)->where('status', 'active')->whereNotNull('current_version_id')->get()
            ->filter(fn (QaDocument $d) => $d->current && ! QaAcknowledgement::query()->where(['version_id' => $d->current_version_id, 'user_id' => $user->id])->exists() && $this->audience($d)->contains($user->id))->values();
    }

    public function acknowledge(QaDocument $doc, User $user): QaAcknowledgement
    {
        if (! $doc->current_version_id || $doc->status !== 'active') {
            throw ValidationException::withMessages(['status' => __('This document is not in force.')]);
        }

        return QaAcknowledgement::query()->firstOrCreate(['version_id' => $doc->current_version_id, 'user_id' => $user->id], ['organization_id' => $doc->organization_id, 'document_id' => $doc->id, 'acknowledged_at' => now()]);
    }

    /** @return array{required:int,acknowledged:int,missing:Collection<int,int>} */
    public function acknowledgement(QaDocument $doc): array
    {
        $audience = $this->audience($doc);
        $done = $doc->current_version_id ? QaAcknowledgement::query()->where('version_id', $doc->current_version_id)->pluck('user_id')->map(fn ($i) => (int) $i) : collect();

        return ['required' => $audience->count(), 'acknowledged' => $audience->intersect($done)->count(), 'missing' => $audience->diff($done)->values()];
    }

    /** Tell the owner about documents whose review is near or past. @return int */
    public function alertReviews(int $organizationId): int
    {
        $limit = today()->addDays($this->policy->int('document.review_warning_days', 30))->toDateString();
        $n = 0;
        foreach (QaDocument::query()->with('current')->where('organization_id', $organizationId)->where('status', 'active')->get() as $d) {
            if ($d->current?->review_due_on && $d->current->review_due_on->toDateString() <= $limit && $d->owner_id) {
                $this->alerts->toUsers($organizationId, [$d->owner_id], 'info', __(':c is due for review on :d.', ['c' => $d->code, 'd' => $d->current->review_due_on->toDateString()]), QaDocument::class, $d->id, 'qa-doc-due-'.$d->id.'-'.$d->current_version_id);
                $n++;
            }
        }

        return $n;
    }
}
