<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** What every lookup behind a searchable dropdown shares: the person must already hold a right on a screen that picks the thing, and only their own hospital's records are ever returned. */
abstract class LookupController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function organizationId(Request $request, array $extra = []): int
    {
        abort_unless(Gate::any(array_merge((array) config('ux.lookup_permissions'), $extra)), 403);

        return (int) $this->tenant->getOrganizationId();
    }

    /** A search word made safe to put inside a LIKE: ! % and _ are plain letters (the query says ESCAPE '!', which both MySQL and SQLite understand). */
    protected function like(string $word): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%';
    }

    /**
     * One search word may match any of these columns.
     *
     * @param  list<string>  $columns
     */
    protected function matching(Builder $query, array $columns, string $word): void
    {
        $like = $this->like($word);
        $query->where(function ($w) use ($columns, $like) {
            foreach ($columns as $column) {
                $w->orWhereRaw("$column like ? escape '!'", [$like]);
            }
        });
    }

    /** @return list<string> */
    protected function words(Request $request): array
    {
        return preg_split('/\s+/u', trim((string) $request->query('q', '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
