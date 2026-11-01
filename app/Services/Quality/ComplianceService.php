<?php

namespace App\Services\Quality;

use App\Models\Quality\QaComplianceItem;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/** The calendar of licences, inspections and certificates the hospital must keep current, with an owner and a warning before each is due. */
class ComplianceService
{
    public function __construct(private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $d kind, title, authority, reference, due_on, interval_months, owner_id, branch_id */
    public function add(int $organizationId, array $d, User $by): QaComplianceItem
    {
        if (! in_array($d['kind'] ?? '', $this->policy->list('compliance.kinds'), true)) {
            throw ValidationException::withMessages(['kind' => __('Choose the kind of item.')]);
        }
        if (mb_strlen(trim((string) ($d['title'] ?? ''))) < 3 || empty($d['due_on'])) {
            throw ValidationException::withMessages(['title' => __('Give the item a name and a due date.')]);
        }
        $item = QaComplianceItem::create(['organization_id' => $organizationId, 'branch_id' => $d['branch_id'] ?? null, 'kind' => $d['kind'], 'title' => mb_substr(trim($d['title']), 0, 200), 'authority' => isset($d['authority']) ? mb_substr($d['authority'], 0, 150) : null,
            'reference' => isset($d['reference']) ? mb_substr($d['reference'], 0, 80) : null, 'due_on' => $d['due_on'], 'interval_months' => ! empty($d['interval_months']) ? (int) $d['interval_months'] : null, 'owner_id' => $d['owner_id'] ?? $by->id, 'status' => 'active']);
        $this->audit->log('CREATE', QaComplianceItem::class, $item->id, null, ['title' => $item->title]);

        return $item;
    }

    /** Record that it was renewed or inspected; the next due date follows the interval. */
    public function complete(QaComplianceItem $item, User $by, ?string $doneOn = null, ?string $nextDue = null): QaComplianceItem
    {
        if ($item->status !== 'active') {
            throw ValidationException::withMessages(['status' => __('This item is retired.')]);
        }
        $done = $doneOn ?: today()->toDateString();
        $next = $nextDue ?: ($item->interval_months ? date('Y-m-d', strtotime("$done +{$item->interval_months} months")) : null);
        if ($next === null) {
            throw ValidationException::withMessages(['next_due' => __('Give the next due date.')]);
        }
        $item->update(['last_done_on' => $done, 'due_on' => $next, 'alerted_at' => null]);
        $this->audit->log('UPDATE', QaComplianceItem::class, $item->id, null, ['done' => $done, 'next' => $next]);

        return $item;
    }

    public function retire(QaComplianceItem $item, User $by, string $reason): QaComplianceItem
    {
        if (mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['reason' => __('Say why.')]);
        }
        $item->update(['status' => 'retired']);
        $this->audit->log('UPDATE', QaComplianceItem::class, $item->id, null, ['status' => 'retired']);

        return $item;
    }

    /** overdue | due_soon | current */
    public function standing(QaComplianceItem $item): string
    {
        $due = $item->due_on->toDateString();
        if ($due < today()->toDateString()) {
            return 'overdue';
        }

        return $due <= today()->addDays($this->policy->int('compliance.warning_days', 60))->toDateString() ? 'due_soon' : 'current';
    }

    /** Warn the owner (and the compliance managers) once a week about each item that is near or past due. @return int warned */
    public function alertDue(int $organizationId): int
    {
        $n = 0;
        foreach (QaComplianceItem::query()->where('organization_id', $organizationId)->where('status', 'active')->get() as $item) {
            $standing = $this->standing($item);
            if ($standing === 'current' || ($item->alerted_at && $item->alerted_at->gt(now()->subDays(7)))) {
                continue;
            }
            $message = $standing === 'overdue' ? __(':t is overdue (due :d).', ['t' => $item->title, 'd' => $item->due_on->toDateString()]) : __(':t is due on :d.', ['t' => $item->title, 'd' => $item->due_on->toDateString()]);
            $this->alerts->toUsers($organizationId, array_filter([$item->owner_id]), $standing === 'overdue' ? 'warning' : 'info', $message, QaComplianceItem::class, $item->id, 'qa-comp-'.$item->id.'-'.today()->toDateString());
            $item->update(['alerted_at' => now()]);
            $n++;
        }

        return $n;
    }
}
