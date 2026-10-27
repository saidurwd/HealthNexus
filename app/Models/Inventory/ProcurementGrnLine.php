<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementGrnLine extends Model
{
    use HasFactory;

    protected $table = 'procurement_grn_lines';

    protected $guarded = ['id'];

    protected $casts = ['received_quantity' => 'decimal:4', 'accepted_quantity' => 'decimal:4', 'rejected_quantity' => 'decimal:4', 'unit_cost' => 'decimal:4', 'expiry_date' => 'date'];

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** @return BelongsTo<ProcurementPoLine, $this> */
    public function poLine(): BelongsTo
    {
        return $this->belongsTo(ProcurementPoLine::class, 'po_line_id');
    }
}
