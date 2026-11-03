<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Item master, categories, units of measure and stores (S01, S03). Drugs stay in the Phase 7 master; an item only points at one. */
class InventoryMasterService
{
    public function __construct(private readonly InventoryPolicy $policy) {}

    /** @param  array{code: string, name: string, kind?: string}  $data */
    public function createCategory(int $organizationId, array $data): InventoryCategory
    {
        $kind = $data['kind'] ?? 'consumable';
        if (! in_array($kind, (array) $this->policy->get('category_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => 'Not a known category kind.']);
        }

        return InventoryCategory::create(['organization_id' => $organizationId, 'code' => strtoupper($data['code']), 'name' => $data['name'], 'kind' => $kind]);
    }

    /**
     * @param  array<string, mixed>  $data  code, name, base_uom, purchase_uom?, purchase_to_base?, tracks_*, issue_rule?, reorder_min/max?, lead_time_days?, standard_cost?, charge_service_code?, medication_id?
     * @param  array<string, float|int|string>  $uoms  extra units: name => base units in one
     */
    public function createItem(InventoryCategory $category, array $data, array $uoms = []): InventoryItem
    {
        $data['code'] = strtoupper((string) $data['code']);
        $this->validateItem($category->organization_id, $data);

        return DB::transaction(function () use ($category, $data, $uoms) {
            $item = InventoryItem::create($data + ['organization_id' => $category->organization_id, 'category_id' => $category->id]);
            if (! empty($data['purchase_uom']) && bccomp((string) ($data['purchase_to_base'] ?? '1'), '1', 4) !== 0) {
                $uoms[$data['purchase_uom']] = $data['purchase_to_base'];
            }
            foreach ($uoms as $uom => $factor) {
                $item->uoms()->create(['uom' => $uom, 'factor_to_base' => $factor]);
            }

            return $item->refresh();
        });
    }

    /** @param  array<string, mixed>  $data */
    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $this->validateItem($item->organization_id, $data + ['code' => $item->code, 'base_uom' => $item->base_uom], $item->id);
        unset($data['code']); // a code is never re-used for something else
        $item->update($data);

        return $item->refresh();
    }

    /** base units for a quantity given in one of the item's units. */
    public function toBase(InventoryItem $item, float|int|string $quantity, ?string $uom = null): string
    {
        if ($uom === null || $uom === $item->base_uom) {
            return bcadd((string) $quantity, '0', 4);
        }
        $factor = $item->uoms()->where('uom', $uom)->value('factor_to_base');
        if ($factor === null) {
            throw ValidationException::withMessages(['uom' => "{$item->name} has no conversion for [$uom]."]);
        }

        return bcmul((string) $quantity, (string) $factor, 4);
    }

    /** @param  array{code: string, name: string, kind?: string, branch_id?: ?int, department_id?: ?int, allow_negative?: bool}  $data */
    public function createStore(int $organizationId, array $data): InventoryStore
    {
        $kind = $data['kind'] ?? 'central';
        if (! in_array($kind, (array) $this->policy->get('store_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => 'Not a known store kind.']);
        }

        return InventoryStore::create(['organization_id' => $organizationId, 'code' => strtoupper($data['code']), 'name' => $data['name'], 'kind' => $kind, 'branch_id' => $data['branch_id'] ?? null, 'department_id' => $data['department_id'] ?? null, 'allow_negative' => $data['allow_negative'] ?? false]);
    }

    public function addLocation(InventoryStore $store, string $code, string $name): InventoryLocation
    {
        return $store->locations()->create(['code' => strtoupper($code), 'name' => $name]);
    }

    /** The store where rejected, recalled and held stock waits (one per organization, made on first use). */
    public function quarantineStore(int $organizationId): InventoryStore
    {
        return InventoryStore::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => 'QRN'], ['name' => 'Quarantine', 'kind' => 'quarantine', 'allow_negative' => false, 'is_active' => true]);
    }

    /** @param  array<string, mixed>  $data */
    private function validateItem(int $organizationId, array $data, ?int $ignoreId = null): void
    {
        $exists = InventoryItem::query()->where('organization_id', $organizationId)->where('code', strtoupper((string) $data['code']))->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['code' => 'That item code is already used.']);
        }
        if (($data['issue_rule'] ?? 'fefo') !== 'fefo' && ($data['issue_rule'] ?? 'fefo') !== 'fifo') {
            throw ValidationException::withMessages(['issue_rule' => 'Use fefo or fifo.']);
        }
        if (! empty($data['tracks_expiry']) && empty($data['tracks_batch'])) {
            throw ValidationException::withMessages(['tracks_expiry' => 'An item with an expiry date must also be tracked by batch.']);
        }
        if (isset($data['reorder_min'], $data['reorder_max']) && bccomp((string) $data['reorder_max'], (string) $data['reorder_min'], 4) < 0) {
            throw ValidationException::withMessages(['reorder_max' => 'The maximum cannot be below the minimum.']);
        }
        if (isset($data['purchase_to_base']) && bccomp((string) $data['purchase_to_base'], '0', 4) <= 0) {
            throw ValidationException::withMessages(['purchase_to_base' => 'A purchase unit holds at least some base units.']);
        }
    }
}
