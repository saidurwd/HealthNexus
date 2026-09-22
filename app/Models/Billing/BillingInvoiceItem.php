<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoiceItem extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['invoice_id', 'charge_id', 'billing_item_id', 'description', 'quantity', 'unit_price', 'gross_amount', 'discount_type', 'discount_amount', 'tax_rate', 'tax_amount', 'net_amount'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'gross_amount' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_rate' => 'decimal:4', 'tax_amount' => 'decimal:2', 'net_amount' => 'decimal:2'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function charge(): BelongsTo { return $this->belongsTo(BillingCharge::class); }
    public function billingItem(): BelongsTo { return $this->belongsTo(BillingItem::class); }

    /**
     * This table carries no company_id/branch_id columns of its own — tenant scope is inherited
     * from the parent invoice, so this filters via that relation instead of a literal column.
     */
    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->whereHas('invoice', fn ($q) => $q->forTenant($companyId, $branchId));
    }
}
