<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountLine extends Model
{
    use HasFactory;

    protected $table = 'inventory_count_lines';

    protected $guarded = ['id'];

    protected $casts = ['system_quantity' => 'decimal:4', 'counted_quantity' => 'decimal:4', 'variance' => 'decimal:4'];

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
