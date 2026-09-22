<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCorporateContract extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'corporate_id', 'price_list_id', 'name', 'discount_type', 'discount_value', 'credit_limit', 'payment_terms_days', 'effective_from', 'effective_to', 'status'];

    protected function casts(): array
    {
        return ['discount_value' => 'decimal:2', 'credit_limit' => 'decimal:2', 'payment_terms_days' => 'integer', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function corporate(): BelongsTo { return $this->belongsTo(BillingCorporate::class); }
    public function priceList(): BelongsTo { return $this->belongsTo(BillingPriceList::class); }
    public function members(): HasMany { return $this->hasMany(BillingCorporateMember::class, 'corporate_contract_id'); }
    public function invoices(): HasMany { return $this->hasMany(BillingInvoice::class, 'corporate_contract_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
