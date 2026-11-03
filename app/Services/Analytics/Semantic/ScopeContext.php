<?php

namespace App\Services\Analytics\Semantic;

use App\Models\User;

/**
 * Who is asking, and what they may see. The compiler applies this to every query: the hospital is always the user's (never taken from the request); a user limited to some branches sees only
 * those branches (and nothing from facts that are not kept by branch); small counts are hidden from people without the sensitive-data right.
 */
final class ScopeContext
{
    /** @param list<int>|null $branchIds null = every branch of the hospital */
    public function __construct(public readonly int $organizationId, public readonly ?array $branchIds, public readonly bool $canSeeSmallNumbers) {}

    public static function for(User $user, int $organizationId): self
    {
        $all = $user->can('analytics.scope.organization');
        $branches = $all ? null : $user->branches()->where('branches.organization_id', $organizationId)->pluck('branches.id')->map(fn ($i) => (int) $i)->all();

        return new self($organizationId, $branches, $user->can('analytics.sensitive.view'));
    }

    /** The whole hospital, everything visible: for system jobs (scheduled reports for an owner who has the right are built with the owner's own scope instead). */
    public static function system(int $organizationId): self
    {
        return new self($organizationId, null, true);
    }
}
