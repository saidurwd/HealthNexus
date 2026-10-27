<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_items';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['purchase_to_base' => 'decimal:4', 'reorder_min' => 'decimal:4', 'reorder_max' => 'decimal:4', 'standard_cost' => 'decimal:4', 'last_cost' => 'decimal:4', 'tracks_batch' => 'boolean', 'tracks_expiry' => 'boolean', 'tracks_serial' => 'boolean'];

    /** @return BelongsTo<InventoryCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /** @return HasMany<InventoryItemUom, $this> */
    public function uoms(): HasMany
    {
        return $this->hasMany(InventoryItemUom::class, 'item_id');
    }

    /** @return HasMany<InventoryBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'item_id');
    }
}
