<?php

namespace App\Services\Reports;

use App\Models\User;

final class ReportContext
{
    public function __construct(
        public readonly User $user,
        public readonly int $organizationId,
        public readonly ?int $branchId,
        public readonly array $filters,
    ) {}
}
