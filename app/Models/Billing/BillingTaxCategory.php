<?php

namespace App\Models\Billing;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingTaxCategory extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'branch_id', 'name', 'code', 'rate', 'is_inclusive', 'is_active', 'effective_from', 'effective_to'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:4', 'is_inclusive' => 'boolean', 'is_active' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingItem::class, 'tax_category_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
