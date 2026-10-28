<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared guards for the staff screens of the patient portal and CRM: a record of another organization does not exist (404); a missing permission is 403.
 * Forms send public ids; the server turns them into records of THIS organization.
 */
abstract class PortalStaffController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
    }

    protected function scoped(Request $request, Model $record, string $permission): Model
    {
        abort_unless($record->getAttribute('organization_id') === $this->tenant->getOrganizationId(), 404);
        $this->allow($request, $permission);

        return $record;
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
