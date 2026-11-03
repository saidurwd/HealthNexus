<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryIssue extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'inventory_issues';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['issued_at' => 'datetime'];

    /** @return HasMany<InventoryIssueLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InventoryIssueLine::class, 'issue_id');
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
