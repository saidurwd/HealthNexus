<?php

namespace App\Services\Billing;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingItemService
{
    public function createCategory(array $data, User $user): BillingCategory
    {
        return DB::transaction(function () use ($data, $user) {
            return BillingCategory::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function updateCategory(BillingCategory $category, array $data, User $user): BillingCategory
    {
        $category->update([...$data, 'updated_by' => $user->id]);

        return $category->refresh();
    }

    public function createItem(array $data, User $user): BillingItem
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['item_code']);

            return BillingItem::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function updateItem(BillingItem $item, array $data, User $user): BillingItem
    {
        return DB::transaction(function () use ($item, $data, $user) {
            if (isset($data['item_code']) && $data['item_code'] !== $item->item_code) {
                $this->assertUniqueCode($item->company_id, $item->branch_id, $data['item_code'], $item->id);
            }

            $item->update([...$data, 'updated_by' => $user->id]);

            return $item->refresh();
        });
    }

    /**
     * Items and categories are never hard-deleted — charges and invoice items reference them
     * historically. Deactivation is a soft delete plus is_active=false.
     */
    public function deactivateItem(BillingItem $item, User $user): void
    {
        $item->update(['is_active' => false, 'updated_by' => $user->id]);
        $item->delete();
    }

    public function deactivateCategory(BillingCategory $category, User $user): void
    {
        $category->update(['is_active' => false, 'updated_by' => $user->id]);
        $category->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $itemCode, ?int $exceptId = null): void
    {
        $exists = BillingItem::query()
            ->forTenant($companyId, $branchId)
            ->where('item_code', $itemCode)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['item_code' => 'This item code is already in use.']);
        }
    }
}
