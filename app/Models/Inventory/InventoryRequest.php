<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryRequest extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_requests';

    protected $guarded = ['id', 'public_id'];

    /** @return HasMany<InventoryRequestLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InventoryRequestLine::class, 'request_id');
    }

    /** @return BelongsTo<InventoryStore, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(InventoryStore::class, 'store_id');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
