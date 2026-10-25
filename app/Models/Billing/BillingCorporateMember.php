<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCorporateMember extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'branch_id', 'corporate_id', 'corporate_contract_id', 'patient_id', 'member_number', 'employee_id', 'relationship', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function corporate(): BelongsTo { return $this->belongsTo(BillingCorporate::class); }
    public function contract(): BelongsTo { return $this->belongsTo(BillingCorporateContract::class, 'corporate_contract_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
