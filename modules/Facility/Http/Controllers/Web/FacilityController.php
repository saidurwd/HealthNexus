<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/** Shared guards for the facility screens: another hospital's record does not exist (404); a missing right is 403; the hospital is always the signed-in user's. */
abstract class FacilityController extends Controller
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

    protected function branchId(): ?int
    {
        $b = $this->tenant->getBranchId();

        return $b ? (int) $b : null;
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

    /** People of this hospital, for the "who" lists. @return Collection<int,User> */
    protected function staff(): Collection
    {
        return User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->orderBy('name')->get(['id', 'name']);
    }
}
