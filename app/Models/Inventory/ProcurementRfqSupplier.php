<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRfqSupplier extends Model
{
    use HasFactory;

    protected $table = 'procurement_rfq_suppliers';

    protected $guarded = ['id'];

    protected $casts = ['invited_at' => 'datetime'];

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }
}
