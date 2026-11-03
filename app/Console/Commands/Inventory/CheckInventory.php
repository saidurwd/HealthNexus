<?php

namespace App\Console\Commands\Inventory;

use App\Models\Organization;
use App\Models\User;
use App\Services\Inventory\InventoryExpiryService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\SupplierService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

/** Daily housekeeping for Stores and purchasing: expire batches, propose write-offs, warn about expiry, supplier documents and emergency orders nobody has approved. */
class CheckInventory extends Command
{
    protected $signature = 'inventory:check';

    protected $description = 'Block expired batches, propose their write-off, warn about stock and supplier documents about to expire and emergency orders not yet approved';

    public function handle(InventoryExpiryService $expiry, SupplierService $suppliers, PurchaseOrderService $orders, ModuleManager $modules): int
    {
        foreach (Organization::query()->where('is_active', true)->get() as $organization) {
            if (! $modules->isEnabled('inventory', $organization->id)) {
                continue;
            }
            $recipients = User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['inventory_manager', 'procurement_manager', 'storekeeper']))->limit(20)->pluck('id')->all();
            $result = $expiry->sweep($organization->id, null, $recipients);
            $docs = $suppliers->raiseDocumentAlerts($organization->id, $recipients);
            $overdue = $orders->overdueRatifications($organization->id)->count();
            $this->info("{$organization->code}: {$result['expired']} batch(es) expired, {$result['alerts']} expiry warning(s), $docs supplier document warning(s), $overdue emergency order(s) not approved in time.");
        }

        return self::SUCCESS;
    }
}
