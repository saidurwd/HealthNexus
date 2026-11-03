<?php

namespace App\Services\Quality;

use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Throwable;

/** Alerts to the people who hold a quality right. An alert that cannot be raised never stops the work that caused it. */
class QualityAlerts
{
    /** @return list<int> */
    public function recipients(int $organizationId, string $permission): array
    {
        return User::query()->permission($permission)->where('is_active', true)
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->limit(30)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    public function raise(int $organizationId, string $severity, string $message, string $sourceType, int $sourceId, string $dedupKey, string $permission = 'quality.incident.triage', bool $ack = true, ?int $branchId = null): void
    {
        try {
            app(AlertService::class)->raise(new AlertRequest(organizationId: $organizationId, category: 'quality', severity: $severity, message: $message, sourceModule: 'quality', branchId: $branchId,
                sourceType: $sourceType, sourceId: $sourceId, recipientUserIds: $this->recipients($organizationId, $permission), ackRequired: $ack, dedupKey: $dedupKey));
        } catch (Throwable) {
            // a report must never be lost because an alert could not be sent
        }
    }

    /** A word to named people (the owner of an action, the lead of an investigation). */
    public function toUsers(int $organizationId, array $userIds, string $severity, string $message, string $sourceType, int $sourceId, string $dedupKey): void
    {
        try {
            app(AlertService::class)->raise(new AlertRequest(organizationId: $organizationId, category: 'quality', severity: $severity, message: $message, sourceModule: 'quality',
                sourceType: $sourceType, sourceId: $sourceId, recipientUserIds: array_values(array_unique($userIds)), ackRequired: false, dedupKey: $dedupKey));
        } catch (Throwable) {
        }
    }
}
