<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchDimension extends Loader
{
    public function key(): string
    {
        return 'dim_branch';
    }

    public function label(): string
    {
        return 'Branches';
    }

    public function kind(): string
    {
        return 'dimension';
    }

    protected function target(): string
    {
        return 'dim_branch';
    }

    protected function uniqueBy(): array
    {
        return ['branch_id'];
    }

    protected function source(): Builder
    {
        return DB::table('branches')->select('branches.id', 'branches.organization_id', 'branches.name', 'branches.code', 'branches.is_active', 'branches.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'branches.updated_at';
    }

    protected function idColumn(): string
    {
        return 'branches.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['branch_id' => $r->id, 'organization_id' => $r->organization_id, 'name' => $r->name, 'code' => $r->code, 'is_active' => (bool) $r->is_active])->all();
    }
}
