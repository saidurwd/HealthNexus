<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementSupplierReturn extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_supplier_returns';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['credit_amount' => 'decimal:2'];

    /** @return HasMany<ProcurementSupplierReturnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementSupplierReturnLine::class, 'supplier_return_id');
    }

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }
}
