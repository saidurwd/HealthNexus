<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementSupplier extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_suppliers';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['rating' => 'decimal:2', 'qualified_at' => 'datetime'];

    /** @return HasMany<ProcurementSupplierDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementSupplierDocument::class, 'supplier_id');
    }

    /** @return HasMany<ProcurementSupplierEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(ProcurementSupplierEvaluation::class, 'supplier_id');
    }
}
