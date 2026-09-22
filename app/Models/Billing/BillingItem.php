<?php

namespace App\Models\Billing;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'category_id', 'tax_category_id', 'item_code', 'item_type', 'name', 'description', 'unit', 'base_price', 'is_taxable', 'is_clinically_chargeable', 'clinical_event_type', 'clinical_event_key', 'is_active', 'effective_from', 'effective_to', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['base_price' => 'decimal:2', 'is_taxable' => 'boolean', 'is_clinically_chargeable' => 'boolean', 'is_active' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(BillingCategory::class, 'category_id'); }
    public function taxCategory(): BelongsTo { return $this->belongsTo(BillingTaxCategory::class, 'tax_category_id'); }
    public function priceListItems(): HasMany { return $this->hasMany(BillingPriceListItem::class); }
    public function charges(): HasMany { return $this->hasMany(BillingCharge::class); }
    public function invoiceItems(): HasMany { return $this->hasMany(BillingInvoiceItem::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
