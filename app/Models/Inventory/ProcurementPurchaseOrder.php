<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementPurchaseOrder extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_purchase_orders';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['order_date' => 'date', 'expected_on' => 'date', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2', 'ratify_by' => 'datetime', 'ratified_at' => 'datetime', 'issued_at' => 'datetime'];

    /** @return HasMany<ProcurementPoLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementPoLine::class, 'purchase_order_id');
    }

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }

    /** @return BelongsTo<InventoryStore, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(InventoryStore::class, 'store_id');
    }

    /** @return HasMany<ProcurementGoodsReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(ProcurementGoodsReceipt::class, 'purchase_order_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return HasMany<ProcurementPoAmendment, $this> */
    public function amendments(): HasMany
    {
        return $this->hasMany(ProcurementPoAmendment::class, 'purchase_order_id');
    }
}
