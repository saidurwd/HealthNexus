<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementGoodsReceipt extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_goods_receipts';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['received_on' => 'date', 'posted_at' => 'datetime'];

    /** @return HasMany<ProcurementGrnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementGrnLine::class, 'goods_receipt_id');
    }

    /** @return BelongsTo<ProcurementPurchaseOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementPurchaseOrder::class, 'purchase_order_id');
    }

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }
}
