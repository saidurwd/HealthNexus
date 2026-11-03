<?php

namespace Modules\Inventory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementSupplier;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared guards for the Inventory and Procurement screens: a record of another organization does not exist (404); a missing permission is 403.
 * Forms send public ids; the server turns them into records of THIS organization (a client-supplied scope is never trusted).
 */
abstract class InventoryController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    /** @param  string  $permission  the full name, e.g. inventory.item.view */
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

    protected function findItem(string $publicId): InventoryItem
    {
        return InventoryItem::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }

    protected function findStore(string $publicId): InventoryStore
    {
        return InventoryStore::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }

    protected function findSupplier(string $publicId): ProcurementSupplier
    {
        return ProcurementSupplier::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }

    protected function findDepartment(int $id): Department
    {
        return Department::query()->where('organization_id', $this->organizationId())->findOrFail($id);
    }

    /** @return Collection<int, InventoryStore> */
    protected function storeChoices(bool $withQuarantine = false)
    {
        return InventoryStore::query()->where('organization_id', $this->organizationId())->where('is_active', true)->when(! $withQuarantine, fn ($q) => $q->where('kind', '!=', 'quarantine'))->orderBy('name')->get();
    }

    protected function departmentChoices()
    {
        return Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']);
    }

    protected function itemChoices()
    {
        return InventoryItem::query()->where(['organization_id' => $this->organizationId(), 'status' => 'active'])->orderBy('name')->get(['id', 'public_id', 'code', 'name', 'base_uom', 'purchase_uom', 'purchase_to_base']);
    }
}
