<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderDimension extends Loader
{
    public function key(): string
    {
        return 'dim_provider';
    }

    public function label(): string
    {
        return 'Providers';
    }

    public function kind(): string
    {
        return 'dimension';
    }

    protected function target(): string
    {
        return 'dim_provider';
    }

    protected function uniqueBy(): array
    {
        return ['provider_id'];
    }

    protected function source(): Builder
    {
        return DB::table('providers')->select('providers.id', 'providers.organization_id', 'providers.branch_id', 'providers.name', 'providers.provider_type', 'providers.department_id', 'providers.status', 'providers.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'providers.updated_at';
    }

    protected function idColumn(): string
    {
        return 'providers.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['provider_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'name' => $r->name, 'provider_type' => $r->provider_type, 'department_id' => $r->department_id, 'status' => $r->status])->all();
    }
}
