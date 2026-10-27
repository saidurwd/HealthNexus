<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransfer extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_transfers';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['dispatched_at' => 'datetime', 'received_at' => 'datetime'];

    /** @return HasMany<InventoryTransferLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InventoryTransferLine::class, 'transfer_id');
    }

    /** @return BelongsTo<InventoryStore, $this> */
    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(InventoryStore::class, 'from_store_id');
    }

    /** @return BelongsTo<InventoryStore, $this> */
    public function toStore(): BelongsTo
    {
        return $this->belongsTo(InventoryStore::class, 'to_store_id');
    }
}
