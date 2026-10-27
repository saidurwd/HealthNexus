<?php

namespace App\Models\Inventory;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementSupplierInvoice extends Model
{
    use HasFactory, HasPublicId;

    protected $table = 'procurement_supplier_invoices';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['invoice_date' => 'date', 'due_on' => 'date', 'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total' => 'decimal:2', 'variance_amount' => 'decimal:2', 'match_result' => 'array'];

    /** @return HasMany<ProcurementSupplierInvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(ProcurementSupplierInvoiceLine::class, 'invoice_id');
    }

    /** @return BelongsTo<ProcurementSupplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplier::class, 'supplier_id');
    }

    /** @return BelongsTo<ProcurementPurchaseOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementPurchaseOrder::class, 'purchase_order_id');
    }
}
