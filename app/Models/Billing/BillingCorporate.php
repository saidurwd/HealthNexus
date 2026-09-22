<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingCorporate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'name', 'code', 'contact_name', 'contact_email', 'contact_phone', 'credit_limit', 'payment_terms_days', 'billing_cycle', 'status'];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:2', 'payment_terms_days' => 'integer'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function contracts(): HasMany { return $this->hasMany(BillingCorporateContract::class, 'corporate_id'); }
    public function members(): HasMany { return $this->hasMany(BillingCorporateMember::class, 'corporate_id'); }
    public function invoices(): HasMany { return $this->hasMany(BillingInvoice::class, 'corporate_id'); }
    public function payments(): HasMany { return $this->hasMany(BillingPayment::class, 'corporate_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
