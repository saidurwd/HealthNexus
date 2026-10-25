<?php

namespace App\Services\Ops;

use App\Models\Organization;
use App\Services\TenantContextResolver;

/**
 * A training organization is marked in organizations.settings['training'] (set by `demo:build`). In training mode every screen carries a
 * "TRAINING" banner, printed documents are stamped, and nothing is sent to real people (e-mail is blocked; SMS has no gateway yet).
 */
class TrainingMode
{
    public function __construct(private readonly TenantContextResolver $tenant) {}

    public function active(?Organization $organization = null): bool
    {
        $organization ??= $this->tenant->getOrganization();

        return (bool) ($organization?->settings['training'] ?? false);
    }
}
