<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementQuotation extends Model
{
    use HasFactory;

    protected $table = 'procurement_quotations';

    protected $guarded = ['id'];

    protected $casts = ['total' => 'decimal:2', 'valid_until' => 'date'];

    /** @return HasMany<ProcurementQuotationLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementQuotationLine::class, 'quotation_id');
    }

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }
}
