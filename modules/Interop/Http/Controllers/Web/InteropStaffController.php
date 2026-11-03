<?php

namespace Modules\Interop\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Shared guards for the integration screens: a record of another hospital does not exist (404); a missing permission is 403. */
abstract class InteropStaffController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
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
        return $class::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }
}
