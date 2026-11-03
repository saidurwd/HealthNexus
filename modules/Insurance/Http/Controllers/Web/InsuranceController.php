<?php

namespace Modules\Insurance\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Shared guards for the Insurance screens: a record of another organization does not exist (404); a missing permission is 403. */
abstract class InsuranceController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can('insurance.'.$permission), 403);
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
}
