<?php

namespace Modules\Watch\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Shared guards for the disease-watch screens: another hospital's record does not exist (404); a missing right is 403. */
abstract class WatchController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string ...$permissions): void
    {
        abort_unless(collect($permissions)->contains(fn ($p) => $request->user()->can($p)), 403);
    }

    protected function organizationId(): int
    {
        return (int) $this->tenant->getOrganizationId();
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function find(string $class, string $publicId): Model
    {
        return $class::query()->where('public_id', $publicId)->where('organization_id', $this->organizationId())->firstOrFail();
    }
}
