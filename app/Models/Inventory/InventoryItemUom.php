<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItemUom extends Model
{
    use HasFactory;

    protected $table = 'inventory_item_uoms';

    protected $guarded = ['id'];

    protected $casts = ['factor_to_base' => 'decimal:4'];
}
