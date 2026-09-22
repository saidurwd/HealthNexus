<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingAdvanceAccount extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'patient_id', 'currency', 'balance', 'status'];

    protected function casts(): array { return ['balance' => 'decimal:2']; }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function transactions(): HasMany { return $this->hasMany(BillingAdvanceTransaction::class, 'advance_account_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
