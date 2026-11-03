<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStore extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_stores';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['allow_negative' => 'boolean', 'is_active' => 'boolean'];

    /** @return HasMany<InventoryLocation, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(InventoryLocation::class, 'store_id');
    }
}
