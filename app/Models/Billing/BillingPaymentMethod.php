<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'branch_id', 'name', 'code', 'type', 'metadata', 'is_active'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(\App\Models\Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function payments(): HasMany { return $this->hasMany(BillingPayment::class, 'payment_method_id'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
