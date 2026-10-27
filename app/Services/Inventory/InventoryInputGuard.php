<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use Illuminate\Validation\ValidationException;

/** Cross-checks that ids supplied from a screen belong together (scope ids come from the server, never trusted from the client). */
class InventoryInputGuard
{
    public function sameOrganization(InventoryStore $store, InventoryItem $item): void
    {
        if ($store->organization_id !== $item->organization_id) {
            throw ValidationException::withMessages(['item' => 'That item belongs to another organization.']);
        }
    }
}
