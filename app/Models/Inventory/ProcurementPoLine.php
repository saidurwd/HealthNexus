<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementPoLine extends Model
{
    use HasFactory;

    protected $table = 'procurement_po_lines';

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'tax_percent' => 'decimal:2', 'line_total' => 'decimal:2', 'received_quantity' => 'decimal:4', 'rejected_quantity' => 'decimal:4', 'closed' => 'boolean'];

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** @return BelongsTo<ProcurementPurchaseOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementPurchaseOrder::class, 'purchase_order_id');
    }
}
