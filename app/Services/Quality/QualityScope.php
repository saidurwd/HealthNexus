<?php

namespace App\Services\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** What a person may see. The hospital is always the signed-in user's; people who manage quality see every branch, others only their own branches. */
class QualityScope
{
    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<T>  $query
     * @return Builder<T>
     */
    public function apply(Builder $query, User $user, int $organizationId, string $branchColumn = 'branch_id'): Builder
    {
        $table = $query->getModel()->getTable();
        $query->where("$table.organization_id", $organizationId);
        if ($user->can('quality.incident.triage') || $user->can('quality.dashboard.view')) {
            return $query;
        }
        $branches = $user->branches()->where('branches.organization_id', $organizationId)->pluck('branches.id')->map(fn ($i) => (int) $i)->all();

        return $query->where(fn ($q) => $q->whereNull("$table.$branchColumn")->orWhereIn("$table.$branchColumn", $branches));
    }
}
