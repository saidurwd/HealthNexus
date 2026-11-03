<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Inventory\InventoryBatch;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryStore;
use App\Models\Inventory\ProcurementGoodsReceipt;
use App\Models\Inventory\ProcurementPurchaseOrder;
use App\Models\Inventory\ProcurementSupplier;
use App\Models\Organization;
use App\Models\User;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Inventory\InventoryMasterService;
use App\Services\Inventory\InventoryStockService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\SupplierService;
use App\Services\TenantContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class InventoryTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Branch $branch;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->organization = Organization::factory()->create();
        $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
        $this->user = $this->staff(['super_admin']);
        $this->actingAs($this->user);
        $this->context();
    }

    protected function context(?Organization $organization = null, ?Branch $branch = null): void
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        session()->put(['tenant_organization_id' => $organization->id, 'tenant_branch_id' => $branch->id]);
        app(TenantContextResolver::class)->setOrganizationId($organization->id);
        app(TenantContextResolver::class)->setBranchId($branch->id);
    }

    /** @param  list<string>  $roles */
    protected function staff(array $roles = [], ?Organization $organization = null, ?Branch $branch = null): User
    {
        $organization ??= $this->organization;
        $branch ??= $this->branch;
        $user = User::factory()->create();
        $user->organizations()->attach($organization->id, ['access_level' => 'admin']);
        $user->branches()->attach($branch->id, ['access_level' => 'manager', 'organization_id' => $organization->id]);
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return $user;
    }

    /** A user who holds exactly these permissions (not a super admin), so approval rules are really tested. @param  list<string>  $permissions */
    protected function actor(array $permissions): User
    {
        $user = $this->staff();
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->refresh();
    }

    protected function category(string $kind = 'consumable', ?string $code = null): InventoryCategory
    {
        return app(InventoryMasterService::class)->createCategory($this->organization->id, ['code' => $code ?? 'CAT'.fake()->unique()->numerify('###'), 'name' => 'Category '.fake()->word(), 'kind' => $kind]);
    }

    /** @param  array<string, mixed>  $o */
    protected function item(array $o = []): InventoryItem
    {
        $category = $o['category'] ?? $this->category();
        unset($o['category']);

        return app(InventoryMasterService::class)->createItem($category, $o + ['code' => 'ITM'.fake()->unique()->numerify('####'), 'name' => 'Item '.fake()->unique()->word(), 'base_uom' => 'each', 'standard_cost' => '10', 'tracks_batch' => true, 'tracks_expiry' => true]);
    }

    protected function store(string $kind = 'central', ?string $code = null, bool $allowNegative = false): InventoryStore
    {
        return app(InventoryMasterService::class)->createStore($this->organization->id, ['code' => $code ?? 'ST'.fake()->unique()->numerify('###'), 'name' => ucfirst($kind).' store '.fake()->unique()->numerify('##'), 'kind' => $kind, 'branch_id' => $this->branch->id, 'allow_negative' => $allowNegative]);
    }

    protected function department(): Department
    {
        return Department::factory()->create(['organization_id' => $this->organization->id]);
    }

    /** A supplier with every required document, qualified. */
    protected function supplier(bool $qualified = true): ProcurementSupplier
    {
        $suppliers = app(SupplierService::class);
        $supplier = $suppliers->create($this->organization->id, ['code' => 'SUP'.fake()->unique()->numerify('###'), 'name' => fake()->unique()->company()]);
        foreach (config('inventory.supplier_required_documents') as $type) {
            $suppliers->addDocument($supplier, $type, 'DOC-'.fake()->numerify('####'), now()->subMonth()->toDateString(), now()->addYear()->toDateString());
        }

        return $qualified ? $suppliers->qualify($supplier, $this->user) : $supplier;
    }

    /** Puts stock on a shelf through the one door (opening balance). */
    protected function stock(InventoryStore $store, InventoryItem $item, string $quantity, ?string $batchNo = 'B1', ?string $expiry = null, string $cost = '10'): int
    {
        $stock = app(InventoryStockService::class);
        $batchId = $stock->batch($item, $batchNo, $item->tracks_expiry ? ($expiry ?? now()->addYear()->toDateString()) : null, null, $cost);
        $stock->receive($store, $item, $batchId, $quantity, 'opening_balance', 'OPEN:'.uniqid(), $cost, null, null, $this->user);

        return $batchId;
    }

    protected function batchOf(int $id): InventoryBatch
    {
        return InventoryBatch::findOrFail($id);
    }

    /** An issued purchase order for the lines [[item, qty, price]], approved by whoever the amount needs (all levels held by one test user per level). */
    protected function issuedOrder(ProcurementSupplier $supplier, InventoryStore $store, array $lines): ProcurementPurchaseOrder
    {
        $orders = app(PurchaseOrderService::class);
        $order = $orders->create($this->user, $supplier, $store, array_map(fn ($l) => ['item_id' => $l[0]->id, 'quantity' => $l[1], 'unit_price' => $l[2]], $lines));
        $orders->submit($order, $this->user);
        foreach ([1, 2, 3] as $level) {
            if ($order->refresh()->status !== 'pending_approval') {
                break;
            }
            $approver = $this->actor(["procurement.approval.level{$level}"]);
            $orders->approve($order, $approver);
        }

        return $orders->issue($order->refresh(), $this->user);
    }

    protected function receiveAll(ProcurementPurchaseOrder $order, ?string $batchPrefix = 'LOT'): ProcurementGoodsReceipt
    {
        $grn = app(GoodsReceiptService::class);
        $lines = $order->lines->map(fn ($l) => ['po_line_id' => $l->id, 'quantity' => $l->quantity, 'batch_no' => $batchPrefix.$l->id, 'expiry_date' => now()->addYear()->toDateString()])->all();

        return $grn->inspect($grn->receive($order, $this->user, $lines), $this->user);
    }
}
