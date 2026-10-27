<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecall extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_recalls';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['quantity_blocked' => 'decimal:4'];

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
