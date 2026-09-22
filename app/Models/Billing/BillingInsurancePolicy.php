<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'insurance_provider_id', 'patient_id', 'policy_number', 'member_number', 'group_number', 'authorization_reference', 'coverage_limit', 'copay_percentage', 'effective_from', 'effective_to', 'status'];

    protected function casts(): array
    {
        return ['coverage_limit' => 'decimal:2', 'copay_percentage' => 'decimal:4', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function provider(): BelongsTo { return $this->belongsTo(BillingInsuranceProvider::class, 'insurance_provider_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function invoices(): HasMany { return $this->hasMany(BillingInvoice::class, 'insurance_policy_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
