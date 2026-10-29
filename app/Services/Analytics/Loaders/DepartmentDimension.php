<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentDimension extends Loader
{
    public function key(): string
    {
        return 'dim_department';
    }

    public function label(): string
    {
        return 'Departments';
    }

    public function kind(): string
    {
        return 'dimension';
    }

    protected function target(): string
    {
        return 'dim_department';
    }

    protected function uniqueBy(): array
    {
        return ['department_id'];
    }

    protected function source(): Builder
    {
        return DB::table('departments')->select('departments.id', 'departments.organization_id', 'departments.branch_id', 'departments.name', 'departments.code', 'departments.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'departments.updated_at';
    }

    protected function idColumn(): string
    {
        return 'departments.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['department_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'name' => $r->name, 'code' => $r->code])->all();
    }
}
