<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequisitionLine extends Model
{
    use HasFactory;

    protected $table = 'procurement_requisition_lines';

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'decimal:4', 'estimated_unit_price' => 'decimal:4', 'ordered_quantity' => 'decimal:4'];

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
