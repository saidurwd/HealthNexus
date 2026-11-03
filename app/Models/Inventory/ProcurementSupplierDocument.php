<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementSupplierDocument extends Model
{
    use HasFactory;

    protected $table = 'procurement_supplier_documents';

    protected $guarded = ['id'];

    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date'];

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }
}
