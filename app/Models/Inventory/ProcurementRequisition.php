<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequisition extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_requisitions';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['needed_by' => 'date', 'estimated_total' => 'decimal:2', 'submitted_at' => 'datetime'];

    /** @return HasMany<ProcurementRequisitionLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementRequisitionLine::class, 'requisition_id');
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
