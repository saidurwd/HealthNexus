<?php

namespace App\Services\Import;

use App\Models\Branch;
use App\Models\User;

final class ImportContext
{
    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $branchId,
        public readonly User $user,
        public readonly array $options = [],
    ) {}

    public function branch(): ?Branch
    {
        return $this->branchId ? Branch::find($this->branchId) : null;
    }
}
