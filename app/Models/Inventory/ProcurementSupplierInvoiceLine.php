<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementSupplierInvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'procurement_supplier_invoice_lines';

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'decimal:4', 'unit_price' => 'decimal:4'];

    /** @return BelongsTo<ProcurementPoLine, $this> */
    public function poLine(): BelongsTo
    {
        return $this->belongsTo(ProcurementPoLine::class, 'po_line_id');
    }
}
