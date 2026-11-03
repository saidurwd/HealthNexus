<?php

namespace App\Services\Clinical\Alerts;

/** What a module hands the Alert Service to raise. `recipientUserIds` are the level-0 recipients. */
final class AlertRequest
{
    /** @param list<int> $recipientUserIds */
    public function __construct(
        public readonly int $organizationId,
        public readonly string $category,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $sourceModule,
        public readonly ?int $patientId = null,
        public readonly ?int $branchId = null,
        public readonly ?int $hospitalId = null,
        public readonly ?string $sourceType = null,
        public readonly string|int|null $sourceId = null,
        public readonly ?string $messageKey = null,
        public readonly array $recipientUserIds = [],
        public readonly bool $ackRequired = true,
        public readonly ?string $escalationPolicy = null,
        public readonly ?string $dedupKey = null,
        public readonly ?int $raisedBy = null,
    ) {}
}
