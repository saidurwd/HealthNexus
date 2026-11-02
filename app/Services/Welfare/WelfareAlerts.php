<?php

namespace App\Services\Welfare;

use App\Models\User;
use App\Services\Clinical\Alerts\AlertRequest;
use App\Services\Clinical\Alerts\AlertService;
use Throwable;

/** Alerts to the people who hold a welfare right. An alert that cannot be raised never stops the work that caused it. */
class WelfareAlerts
{
    /** @return list<int> */
    public function recipients(int $organizationId, string $permission): array
    {
        return User::query()->permission($permission)->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->limit(30)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    public function raise(int $organizationId, string $severity, string $message, string $sourceType, int $sourceId, string $dedupKey, string $permission): void
    {
        try {
            app(AlertService::class)->raise(new AlertRequest(organizationId: $organizationId, category: 'welfare', severity: $severity, message: $message, sourceModule: 'welfare', sourceType: $sourceType, sourceId: $sourceId,
                recipientUserIds: $this->recipients($organizationId, $permission), ackRequired: false, dedupKey: $dedupKey));
        } catch (Throwable) {
            // an alert must never undo the work
        }
    }
}
